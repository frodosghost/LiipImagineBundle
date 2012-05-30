<?php

namespace Liip\ImagineBundle\Renderer;


class ImageRenderer
{
    /**
     * @var \Twig_Environment
     */
    private $environment;

    /**
     * @param \Twig_Environment $environment
     * @param string $template
     */
    public function __construct(\Twig_Environment $environment)
    {
        $this->environment = $environment;
    }

    public function render($image_src, $attributes)
    {
        $template = $this->environment->loadTemplate('LiipImagineBundle:Adaptive:image_tag.html.twig');

        return $template->renderBlock('img_tag', array('src' => $image_src, 'options' => $attributes));
    }

}
