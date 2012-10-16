<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\WmsBundle\Component\Attribution;
use Mapbender\WmsBundle\Component\MetadataUrl;
use Mapbender\WmsBundle\Component\Identifier;

/**
 * Style class.
 */
class Style {


    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $name = "";

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $title = "";
    
    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $abstract = "";
    
    /**
     * ORM\Column(type="object", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $legendUrl;
    

    /**
     * Set name
     *
     * @param string $name
     * @return Style
     */
    public function setName($name)
    {
        $this->name = $name;
    
        return $this;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Style
     */
    public function setTitle($title)
    {
        $this->title = $title;
    
        return $this;
    }

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
     * Set abstract
     *
     * @param string $abstract
     * @return Style
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
    
        return $this;
    }

    /**
     * Get abstract
     *
     * @return string 
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set legendUrl
     *
     * @param LegendUrl $legendUrl
     * @return Style
     */
    public function setLegendUrl(LegendUrl $legendUrl)
    {
        $this->legendUrl = $legendUrl;
    
        return $this;
    }

    /**
     * Get legendUrl
     *
     * @return \stdClass 
     */
    public function getLegendUrl()
    {
        return $this->legendUrl;
    }
}