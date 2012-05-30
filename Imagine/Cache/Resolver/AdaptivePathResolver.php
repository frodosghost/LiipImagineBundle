<?php

namespace Liip\ImagineBundle\Imagine\Cache\Resolver;

use Liip\ImagineBundle\Imagine\Cache\CacheManagerAwareInterface;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Imagine\Exception\InvalidArgumentException;
use Liip\ImagineBundle\Imagine\Filter\Request\RequestInterface;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Resolves multiple paths for multiple images specified in css breakpoints.
 * To be used as an implementation of adaptive images for web site images
 * 
 * A variation - with some code copying - from AdaptiveImages
 * @link https://github.com/MattWilcox/Adaptive-Images
 */
class AdaptivePathResolver extends WebPathResolver
{
    /**
     * @var RequestInterface
     */
    private $request_filter;
    private $logger;

    /**
     * @return RequestInterface
     */
    public function getRequestFilter()
    {
        return $this->request_filter;
    }

    /**
     * @param RequestInterface
     */
    public function setRequestFilter(RequestInterface $request_filter, $logger)
    {
        $this->request_filter = $request_filter;
        $this->logger = $logger;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Request $request, $path, $filter)
    {
        $browserPath = $this->decodeBrowserPath($this->getBrowserPathForImage($path, $filter));
        $targetPath = $this->getFilePath($path, $filter, $request->getBaseUrl());

        // if the file has already been cached, we're probably not rewriting
        // correctly, hence make a 301 to proper location, so browser remembers
        if (file_exists($targetPath)) {
            $scriptName = $request->getScriptName();
            if (strpos($browserPath, $scriptName) === 0) {
                // strip script name
                $browserPath = substr($browserPath, strlen($scriptName));
            }

            return new RedirectResponse($request->getBasePath().$browserPath);
        }

        return $targetPath;
    }

    /**
     * Extends functionality of original to return an array of images to be used in an image tag
     *
     * @param string  $targetPath      Path of original image
     * @param string  $filter          Name of filter used in routing
     * @param boolean $absolute        Link is absolute
     */
    public function getBrowserPath($targetPath, $filter, $absolute = false)
    {
        $image_paths = array();
        $filter_settings = $this->getCacheManager()->getFilterConfig()->get($filter);

        if (empty($filter_settings['breakpoints'])) {
            throw new InvalidArgumentException(sprintf('Please set the image breakpoints in the configuration.'));
        }

        $breakpoints = $this->getImageBreakpoints($filter_settings['breakpoints']);

        foreach ($breakpoints as $name => $width)
        {
            $image_paths[$name] = $this->getImagePath($targetPath, $filter, $name, $absolute);
        }

        return $image_paths;    
    }

    private function getBrowserPathForImage($path, $filter)
    {
        // If resolving an image path remove the breakpoint added to cached image
        $original_image = $this->getOriginalImagePath($path);

        // Get all images with specified 
        $images = $this->getBrowserPath($original_image, $filter);
        $image_namespace = $this->getImageNamespacePath($path);

        return $images[$image_namespace];
    }

    /**
     * {@inheritDoc}
     */
    protected function getFilePath($path, $filter, $basePath = '')
    {
        $browserPath = $this->decodeBrowserPath($this->getBrowserPathForImage($path, $filter));

        // if cache path cannot be determined, return 404
        if (null === $browserPath) {
            throw new NotFoundHttpException('Image doesn\'t exist');
        }

        if (!empty($basePath) && 0 === strpos($browserPath, $basePath)) {
            $browserPath = substr($browserPath, strlen($basePath));
        }

        return $this->getCacheManager()->getWebRoot().$browserPath;
    }

    /**
     * Determines new image path and maps it to the router
     * Returns image path determined by Router
     *
     * @param string  $targetPath      Path of original image
     * @param string  $filter          Name of filter used in routing
     * @param string  $breakpoint_name Name given to breakpoint from css
     * @param boolean $absolute        Link is absolute
     */
    private function getImagePath($targetPath, $filter, $breakpoint_name, $absolute = false)
    {
        // Update path with adaptive breakpoints in file name
        $targetPath = str_replace(
            basename($targetPath),
            $breakpoint_name .'.'. basename($targetPath),
            $targetPath
        );

        $params = array('path' => ltrim($targetPath, '/'));

        return str_replace(
            urlencode($params['path']),
            urldecode($params['path']),
            $this->getCacheManager()->getRouter()->generate('_imagine_'.$filter.'_'.$breakpoint_name, $params, $absolute)
        );
    }

    /**
     * Check browser specified cookie for resolution and return ordered array
     *
     */
    private function getImageBreakpoints($breakpoints)
    {
        // Sort array with lowest number first
        asort($breakpoints);
        // Get the cookie from the filtered request
        $resolutions = $this->getRequestFilter()->filter('resolution');

        // If the cookie is not set and the screen width is 0 return the lowest ordered item first
        if ($resolutions['screen_width'] > 0)
        {
            $total_width = $resolutions['screen_width'] * $resolutions['pixel_density'];
            // Sort by the value and retain the keys
            uasort($breakpoints, $this->resolution_sorter($total_width));
        }

        return $breakpoints;
    }

    /**
     * Decodes the URL encoded browser path.
     *
     * @param string $browserPath
     *
     * @return string
     */
    protected function decodeBrowserPath($browserPath)
    {
        //TODO: find out why I need double urldecode to get a valid path
        return urldecode(urldecode($browserPath));
    }

    /**
     * Removes the Adaptive image namespace before the image
     *
     * @param string $image_path 
     *
     * @return string
     */
    private function getOriginalImagePath($image_path)
    {
        // Return path with original image
        return str_replace(
            basename($image_path),
            preg_replace('/^[^.]*.\s*/', '', basename($image_path)),
            $image_path
        );
    }

    /**
     * Removes the Adaptive image namespace before the image
     *
     * @param string $image_path 
     *
     * @return string
     */
    private function getImageNamespacePath($image_path)
    {
        $breakpoint = null;
        if (preg_match('/^[^.]*\s*/', basename($image_path), $matches))
        {
            $breakpoint = $matches[0];
        }

        return $breakpoint;
    }

    /**
     * Function determines sort order
     */
    private function resolution_sorter($total_width)
    {
        return function ($a, $b) use ($total_width) {
            if ($a == $b) {
                return 0;
            } else if ($a > $b && $a < $total_width) {
                // If A greater than B and less than TOTAL WIDTH move B down
                return -1;
            } else {
                return 1;
            }
        };
    }

}
