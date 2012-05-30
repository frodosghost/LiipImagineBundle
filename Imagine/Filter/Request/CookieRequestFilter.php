<?php

namespace Liip\ImagineBundle\Imagine\Filter\Request;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter for returning values stored in Request Cookeie
 *
 * @author James Rickard <james@frodosghost.com>
 */
class CookieRequestFilter implements RequestInterface
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
     *
     * @param string @name
     */
    public function filter($name)
    {
        $cookies = $this->getServiceContainer()->get('request')->cookies;

        $client_settings = array(
            'screen_width' => 0, // Default value of mobile width
            'pixel_density' => 1 // Default non-retina screens
        );

        if ($cookies->has($name))
        {
            $cookie = $cookies->get($name);
            if (!preg_match("/^[0-9]+[,]*[0-9\.]+$/", $cookie))
            {
                $cookies->set($name, '');
                return $client_settings;
            }

            // Explode the string to determine values            
            $cookie = explode(',', $cookie);
            
            // Base Resolution
            $client_settings['screen_width'] = (int) $cookie[0];

            // The device's pixel density
            if (isset($cookie[1]) && !is_null($cookie[1])) {
                $client_settings['pixel_density'] = (int)$cookie[1];
            }

            return $client_settings;
        }
    }

}
