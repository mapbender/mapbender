<?php


namespace Mapbender\ManagerBundle\Component\Menu;


class TopLevelItem extends MenuItem
{
    /** @var int|null */
    protected $weight;

    /**
     * @param $num
     * @return $this
     */
    public function setWeight($num)
    {
        $this->weight = intval($num);
        return $this;
    }

    /**
     * @return int|null
     */
    public function getWeight()
    {
        return $this->weight;
    }
}
