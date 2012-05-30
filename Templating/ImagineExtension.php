<?php

namespace Liip\ImagineBundle\Templating;

use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Filter\Request\RequestInterface;
use Liip\ImagineBundle\Renderer\ImageRenderer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImagineExtension extends \Twig_Extension
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ImageRenderer
     */
    private $imageRenderer;

    /**
     * Constructs by setting $cachePathResolver
     *
     * @param CacheManager $cacheManager
     * @param ImageRenderer $imageRenderer
     */
    public function __construct(CacheManager $cacheManager, ImageRenderer $imageRenderer)
    {
        $this->cacheManager = $cacheManager;
        $this->imageRenderer = $imageRenderer;
    }

    /**
     * Get the cache manager specified in construct function
     *
     * @return CacheManager
     */
    public function getCacheManager()
    {
        return $this->cacheManager;
    }

    /**
     * Get the image renderer specified in construct function
     *
     * @return ImageRenderer
     */
    public function getImageRenderer()
    {
        return $this->imageRenderer;
    }

    /**
     * (non-PHPdoc)
     * @see Twig_Extension::getFilters()
     */
    public function getFilters()
    {
        return array(
            'imagine_filter' => new \Twig_Filter_Method($this, 'filter')
        );
    }

    /**
     * (non-PHPdoc)
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return array(
            'adaptive_img_tag' => new \Twig_Function_Method($this, 'adaptive_img', array('is_safe' => array('html'))),
        );
    }

    /**
     * Gets cache path of an image to be filtered
     *
     * @param string $path
     * @param string $filter
     * @param boolean $absolute
     *
     * @return string
     */
    public function filter($path, $filter, $absolute = false)
    {
        return $this->getCacheManager()->getBrowserPath($path, $filter, $absolute);
    }

    /**
     * Generates <img /> tag with selected image and alternate adaptive images
     *
     * @param string  $path     Location of original image to generate cached images
     * @param array   $options  Options to pass into image tag
     * @param boolean $absolute Generate absolute links
     *
     * @return string
     */
    public function adaptive_img($path, $options = array(), $absolute = false)
    {
        $attributes = $options;
        $image_array = $this->getCacheManager()->getBrowserPath($path, 'adaptive', $absolute);

        $image_src = array_shift($image_array);
        if (is_array($image_array))
        {
            $attributes = array_merge($attributes, $image_array);
        }

        return $this->getImageRenderer()->render($image_src, $attributes);
    }

    /**
     * (non-PHPdoc)
     * @see Twig_ExtensionInterface::getName()
     */
    public function getName()
    {
        return 'liip_imagine';
    }
}
