<?php

namespace Liip\ImagineBundle\Imagine\Filter\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter for returning values for Adaptive images from Request
 *
 * @author James Rickard <james@frodosghost.com>
 */
class UrlRequestFilter implements RequestInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * Constructs by setting ContainerInterface
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the service container specified in construct function
     *
     * @return ContainerInterface
     */
    public function getServiceContainer()
    {
        return $this->container;
    }

    /**
     * Filter value from the service container
     * Checks the Request URL for the {name} attribute and returns the value
     *
     * @param string @name
     */
    public function filter($name)
    {
        $route_variable = $this->getServiceContainer()->get('request')->attributes->get($name);

        return $route_variable;
    }

}
