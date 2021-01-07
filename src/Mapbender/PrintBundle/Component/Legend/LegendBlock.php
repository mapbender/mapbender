<?php


namespace Mapbender\PrintBundle\Component\Legend;


use Mapbender\PrintBundle\Component\GdCanvas;

class LegendBlock extends GdCanvas implements LegendBlockContainer
{
    /** @var string */
    protected $title;
    /** @var boolean */
    protected $rendered = false;

    /**
     * @param resource $image GDish
     * @param string $title
     */
    public function __construct($image, $title)
    {
        parent::__construct(1, 1);
        imagedestroy($this->resource);
        $this->resource = $image;
        $this->title = $title;
        $this->rendered = false;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param bool $rendered
     */
    public function setIsRendered($rendered)
    {
        $this->rendered = !!$rendered;
    }

    /**
     * Returns true if the block has been marked as already rendered.
     *
     * @return bool
     */
    public function isRendered()
    {
        return $this->rendered;
    }

    public function getBlocks()
    {
        return array($this);
    }
}
