<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\WmsBundle\Component\Attribution;
use Mapbender\WmsBundle\Component\MetadataUrl;
use Mapbender\WmsBundle\Component\Identifier;

/**
 * LegendUrl class.
 */
class OnlineResource {


    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $format;
    
    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $href;

    /**
     * Set format
     *
     * @param string $format
     * @return LegendUrl
     */
    public function setFormat($format)
    {
        $this->format = $format;
    
        return $this;
    }

    /**
     * Get format
     *
     * @return string 
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set href
     *
     * @param string $href
     * @return LegendUrl
     */
    public function setHref($href)
    {
        $this->href = $href;
    
        return $this;
    }

    /**
     * Get href
     *
     * @return string 
     */
    public function getHref()
    {
        return $this->href;
    }
}