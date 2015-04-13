<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Attribution class.
 *
 * @author Paul Schmidt
 */
class Attribution
{
    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $title;

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $onlineResource;

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $logoUrl;

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     * @param string $value
     */
    public function setTitle($value)
    {
        $this->title = $value;
    }

    /**
     * Get onlineResource
     *
     * @return string
     */
    public function getOnlineResource()
    {
        return $this->onlineResource;
    }

    /**
     * Set onlineResource
     * @param string $value
     */
    public function setOnlineResource($value)
    {
        $this->onlineResource = $value;
    }

    /**
     * Get logoUrl
     *
     * @return string
     */
    public function getLogoUrl()
    {
        return $this->logoUrl;
    }

    /**
     * Set logoUrl
     * @param string $value
     */
    public function setLogoUrl(LegendUrl $value)
    {
        $this->logoUrl = $value;
        return $this;
    }
}
