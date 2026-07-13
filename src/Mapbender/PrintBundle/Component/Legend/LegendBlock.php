<?php


namespace Mapbender\PrintBundle\Component\Legend;


use Mapbender\PrintBundle\Component\GdCanvas;

class LegendBlock extends GdCanvas implements LegendBlockContainer
{
    protected bool $rendered;

    /**
     * @param \GdImage $image GDish
     * @param string $title
     */
    public function __construct($image, protected $title)
    {
        parent::__construct(1, 1);
        imagedestroy($this->resource);
        $this->resource = $image;
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
    public function setIsRendered($rendered): void
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

    public function getBlocks(): array
    {
        return [$this];
    }
}
