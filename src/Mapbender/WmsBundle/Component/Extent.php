<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class Extent
{

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $name;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $default;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $multipleValues;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $nearestValue;

    /**
     * ORM\Column(type="boolean", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $current;

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $extentValue;

    /**
     * Set name
     * 
     * @param string $value 
     * @return Dimension
     */
    public function setName($value)
    {
        $this->name = $value;
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
     * Set default
     * 
     * @param string $value 
     * @return Dimension
     */
    public function setDefault($value)
    {
        $this->default = $value;
    }

    /**
     * Get default
     * 
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set multipleValues
     * 
     * @param boolean $value 
     * @return Dimension
     */
    public function setMultipleValues($value)
    {
        $this->multipleValues = $value;
    }

    /**
     * Get multipleValues
     * 
     * @return boolean
     */
    public function getMultipleValues()
    {
        return $this->multipleValues;
    }

    /**
     * Set nearestValue
     * 
     * @param boolean $value 
     * @return Dimension
     */
    public function setNearestValue($value)
    {
        $this->nearestValue = $value;
    }

    /**
     * Get nearestValue
     * 
     * @return boolean
     */
    public function getNearestValue()
    {
        return $this->nearestValue;
    }

    /**
     * Set current
     * 
     * @param boolean $value 
     * @return Dimension
     */
    public function setCurrent($value)
    {
        $this->current = $value;
    }

    /**
     * Get current
     * 
     * @return boolean
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * Set extentValue
     * 
     * @param string $value 
     * @return Dimension
     */
    public function setExtentValue($value)
    {
        $this->extentValue = $value;
    }

    /**
     * Get extentValue
     * 
     * @return string
     */
    public function getExtentValue()
    {
        return $this->extentValue;
    }

}
