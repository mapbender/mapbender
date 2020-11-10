<?php

namespace Mapbender\WmsBundle\Component;

/**
 * @author Paul Schmidt
 */
class LegendUrl
{
    /** @var OnlineResource|null */
    public $onlineResource;

    /** @var int|null */
    public $width;

    /** @var int|null */
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
     * @return $this
     */
    public function setOnlineResource(OnlineResource $onlineResource)
    {
        $this->onlineResource = $onlineResource;
        return $this;
    }

    /**
     * Get onlineResource
     *
     * @return OnlineResource|null
     */
    public function getOnlineResource()
    {
        return $this->onlineResource;
    }

    /**
     * Set width
     *
     * @param int|null $width
     * @return $this
     */
    public function setWidth($width)
    {
        $this->width = $width;
        return $this;
    }

    /**
     * Get width
     *
     * @return int|null
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * Set height
     *
     * @param int|null $height
     * @return $this
     */
    public function setHeight($height)
    {
        $this->height = $height;
        return $this;
    }

    /**
     * Get height
     *
     * @return int|null
     */
    public function getHeight()
    {
        return $this->height;
    }

}