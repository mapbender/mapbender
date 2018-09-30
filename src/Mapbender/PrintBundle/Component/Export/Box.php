<?php


namespace Mapbender\PrintBundle\Component\Export;


class Box
{
    /** @var float */
    public $left;
    /** @var float */
    public $bottom;
    /** @var float */
    public $right;
    /** @var float */
    public $top;

    /**
     * @param int|float $left
     * @param int|float $bottom
     * @param int|float $right
     * @param int|float $top
     */
    public function __construct($left, $bottom, $right, $top)
    {
        $this->left = floatval($left);
        $this->bottom = floatval($bottom);
        $this->right = floatval($right);
        $this->top = floatval($top);
    }

    /**
     * @return float
     */
    public function getWidth()
    {
        return $this->right - $this->left;
    }

    /**
     * @return float
     */
    public function getHeight()
    {
        return $this->top - $this->bottom;
    }
}
