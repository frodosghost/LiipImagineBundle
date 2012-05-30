<?php

namespace Liip\ImagineBundle\Imagine\Filter\Request;

/**
 * Request Filter Interface
 *
 * @author James Rickard <james@frodosghost.com>
 */
interface RequestInterface
{
    /**
     * @param string $name
     *
     * @return array
     */
    function filter($name);
}
