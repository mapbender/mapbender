<?php
namespace Mapbender\WmsBundle\Component;

//use Doctrine\Common\Collections\ArrayCollection;
//use Doctrine\ORM\Mapping as ORM;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\WmsBundle\Component\Attribution;
use Mapbender\WmsBundle\Component\MetadataUrl;
use Mapbender\WmsBundle\Component\Identifier;

/**
 * LegendUrl class.
 */
class LegendUrl {
    
    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $onlineResource;

    /**
     * ORM\Column(type="integer", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $width;
    
    /**
     * ORM\Column(type="integer", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $height;

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