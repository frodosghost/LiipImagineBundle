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
class AdaptivePathResolver extends AbstractFilesystemResolver implements CacheManagerAwareInterface
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var RequestInterface
     */
    private $request_filter;

    /**
     * @var string
     */
    private $css_breakpoint;

    /**
     * @param CacheManager $cacheManager
     */
    public function setCacheManager(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @return CacheManager
     */
    public function getCacheManager()
    {
        return $this->cacheManager;
    }

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
    public function setRequestFilter(RequestInterface $request_filter)
    {
        $this->request_filter = $request_filter;
    }

    /**
     * Returns CSS Breakpoint name as sent in request for image display
     */
    public function getRouteParameter()
    {
        if (null === $this->css_breakpoint) {
            throw new InvalidArgumentException('The route used is incorrect. No {name} parameter has been set');
        }

        return $this->css_breakpoint;
    }

    /**
     * {@inheritDoc}
     */
    public function resolve(Request $request, $path, $filter)
    {
        $this->css_breakpoint = $request->attributes->get('name');

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
        $params = array(
            'path' => ltrim($targetPath, '/'),
            'name' => $breakpoint_name
        );

        return str_replace(
            urlencode($params['path']),
            urldecode($params['path']),
            $this->getCacheManager()->getRouter()->generate('_imagine_'.$filter, $params, $absolute)
        );
    }

    /**
     * Check browser specified cookie for resolution and return ordered array
     *
     * @param array $breakpoints
     *
     * @return array
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
    private function decodeBrowserPath($path)
    {
        //TODO: find out why I need double urldecode to get a valid path
        return urldecode(urldecode($path));
    }

    /**
     * Selects current image as determined from the route parameter
     *
     * @param string $path   Path that is provided from Route
     * @param string $filter Filter that is to be run on the images
     *
     * @return string
     */
    private function getBrowserPathForImage($path, $filter)
    {
        // Get all images with specified 
        $images = $this->getBrowserPath($path, $filter);

        return $images[$this->getRouteParameter()];
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
