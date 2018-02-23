<?php

namespace Mapbender\WmsBundle\Component;

/**
 * LegendUrl class.
 * @author Paul Schmidt
 */
class LegendUrl
{
    /**
     * ORM\Column(type="string", nullable=true)
     */
    public $onlineResource;

    /**
     * ORM\Column(type="integer", nullable=true)
     */
    public $width;

    /**
     * ORM\Column(type="integer", nullable=true)
     */
    public $height;
    
    /**
     * 
     * @param OnlineResource $onlineResource
     * @param int $width
     * @param int $height
     */
    public function __construct(OnlineResource $onlineResource = null, $width = null, $height = null)
    {
        $this->onlineResource = $onlineResource;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Set onlineResource
     *
     * @param OnlineResource $onlineResource
     * @return LegendUrl
     */
    public function setOnlineResource(OnlineResource $onlineResource)
    {
        $this->onlineResource = $onlineResource;

        return $this;
    }

    /**
     * Get onlineResource
     *
     * @return OnlineResource 
     */
    public function getOnlineResource()
    {
        return $this->onlineResource;
    }

    /**
     * Set width
     *
     * @param integer $width
     * @return LegendUrl
     */
    public function setWidth($width)
    {
        $this->width = $width;

        return $this;
    }

    /**
     * Get width
     *
     * @return integer 
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param integer $height
     * @return LegendUrl
     */
    public function setHeight($height)
    {
        $this->height = $height;

        return $this;
    }

    /**
     * Get height
     *
     * @return integer 
     */
    public function getHeight()
    {
        return $this->height;
    }

}