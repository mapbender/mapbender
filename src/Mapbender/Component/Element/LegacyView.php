<?php


namespace Mapbender\Component\Element;


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
