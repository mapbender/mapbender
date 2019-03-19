<?php

namespace Mapbender\WmtsBundle\Entity;

/**
 * Dimension describes:
 * Metadata about a particular dimension that the tiles of a layer are available.
 * @author Paul Schmidt
 */
class Dimension
{
    
    /**
     * A name of dimensional axis.
     * @var string
     */
    public $identifier;

    /**
     * Units of measure of dimensional axis.
     * @var string
     */
    public $oum;

    /**
     * Symbol of the units.
     * @var string
     */
    public $unitsSymbol;

    /**
     * Default value that will be used if a tile request does not specify a value or uses the keyword 'default'.
     * @var string
     */
    public $default;

    /**
     * A value of 1 (or 'true') indicates (a) that temporal data are normally kept current and (b) that the
     * request value of this dimension accepts the keyword 'current'.
     * @var boolean
     */
    public $current;

    /**
     * Available value for this dimension.
     * @var string[]
     */
    public $value = array();

    /**
     * Returns an identifier
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Returns oum.
     * @return string
     */
    public function getOum()
    {
        return $this->oum;
    }

    public function getUnitsSymbol()
    {
        return $this->unitsSymbol;
    }

    /**
     * Returns default.
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Returns current
     * @return boolean
     */
    public function getCurrent()
    {
        return $this->current;
    }

    /**
     * Returns value
     * @return string[]
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets identifier.
     * @param string $identifier
     * @return \Mapbender\WmtsBundle\Entity\Dimension
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * Sets oum.
     * @param string $oum
     * @return \Mapbender\WmtsBundle\Entity\Dimension
     */
    public function setOum($oum)
    {
        $this->oum = $oum;
        return $this;
    }

    /**
     * Sets unitsSymbol.
     * @param string $unitsSymbol
     * @return \Mapbender\WmtsBundle\Entity\Dimension
     */
    public function setUnitsSymbol($unitsSymbol)
    {
        $this->unitsSymbol = $unitsSymbol;
        return $this;
    }

    /**
     * Sets default
     * @param string $default
     * @return \Mapbender\WmtsBundle\Entity\Dimension
     */
    public function setDefault($default)
    {
        $this->default = $default;
        return $this;
    }

    /**
     * Sets current.
     * @param boolean $current
     * @return \Mapbender\WmtsBundle\Entity\Dimension
     */
    public function setCurrent($current)
    {
        $this->current = $current ? true : false;
        return $this;
    }

    /**
     * Sets value
     * @param array $value
     * @return \Mapbender\WmtsBundle\Entity\Dimension
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Adds value
     * @param string $value
     * @return \Mapbender\WmtsBundle\Entity\Dimension
     */
    public function addValue($value)
    {
        $this->value[] = $value;
        return $this;
    }
}
