<?php

namespace Liip\ImagineBundle\Imagine\Filter\Loader;

use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use Liip\ImagineBundle\Imagine\Filter\RelativeResize;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\Imagine\Filter\Request\RequestInterface;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AdaptiveFilterLoader
 *
 * Determines the image ratio and adaptivly assigns either 'widen' or 'heighten' method
 * to image resizing.
 *
 * @author James Rickard <james@frodosghost.com>
 */
class AdaptiveFilterLoader implements LoaderInterface
{
    private $request_filter;

    public function __construct(RequestInterface $request_filter)
    {
        $this->request_filter = $request_filter;
    }

    /**
     * @see Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface::load()
     */
    public function load(ImageInterface $image, array $options = array())
    {
        $specified_image = $this->request_filter->filter('url');
        $image_orientation = 'widen';

        $filter = new RelativeResize($image_orientation, $options['breakpoints'][$specified_image]);

        return $filter->apply($image);
    }

}
