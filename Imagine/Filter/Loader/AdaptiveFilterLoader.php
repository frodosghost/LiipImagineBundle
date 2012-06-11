<?php

namespace Liip\ImagineBundle\Imagine\Filter\Loader;

use Liip\ImagineBundle\Imagine\Filter\Loader\LoaderInterface;
use Liip\ImagineBundle\Imagine\Filter\RelativeResize;
use Imagine\Image\ImageInterface;
use Liip\ImagineBundle\Imagine\Filter\FilterConfiguration;
use Liip\ImagineBundle\Imagine\Filter\Request\RequestInterface;
use Imagine\Exception\InvalidArgumentException;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * AdaptiveFilterLoader
 *
 * Determines the name attribute from the request and resizes the image as specified in filter option
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
        $route_name = $this->request_filter->filter('name');

        if (!isset($options[$route_name]) && $options[$route_name] === null)
        {
            throw new InvalidArgumentException(sprintf('The filter option "%s" was not found in the configuration options', $route_name));
        }

        if (list($method, $parameter) = each($options[$route_name]))
        {
            $filter = new RelativeResize($method, $parameter);

            return $filter->apply($image);
        }

        throw new InvalidArgumentException('Expected method/parameter pair, none given');        
    }

}
