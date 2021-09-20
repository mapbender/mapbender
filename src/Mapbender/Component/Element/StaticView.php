<?php


namespace Mapbender\Component\Element;


/**
 * View for service-type Elements that have no inner content, or inner content
 * so trivial that it can be pre-generated without a twig template.
 */
class StaticView extends ElementView
{
    /** @var string */
    protected $content;

    /**
     * @param string $content
     */
    public function __construct($content)
    {
        $this->content = $content ?: '';
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }
}
