<?php


namespace Mapbender\Component\Element;

/**
 * Captures legacy (non-service, self-rendering) element frontend markup.
 * Captured markup can have any structure, but will most likely contain outer Element
 * tag with id, class and other misc attributes.
 *
 * @see \Mapbender\CoreBundle\Component\Element::render
 */
class LegacyView
{
    /** @var string */
    protected $content;

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
