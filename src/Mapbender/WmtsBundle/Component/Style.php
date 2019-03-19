<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\WmtsBundle\Entity\LegendUrl;

/**
 * Description of Style
 *
 * @author Paul Schmidt
 */
class Style
{
    /**
     * is default style
     * @var boolean
     */
    public $isDefault;

    /**
     * A style title
     * @var string
     */
    public $title;

    /**
     * A style descrioption
     * @var string
     */
    public $abstract;

    /**
     *
     * @var string
     */
    public $identifier;

    /**
     *
     * @var LegendUrl|null
     */
    public $legendurl;

    /**
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * @param boolean $isDefault
     * @return $this
     */
    public function setIsDefault($isDefault)
    {
        $this->isDefault = $isDefault ? true : false;
        return $this;
    }

    /**
     * Get title
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * @return string
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * @param string $abstract
     * @return $this
     */
    public function setAbstract($abstract)
    {
        $this->abstract = $abstract;
        return $this;
    }

    /**
     * Get identfier.
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * Set identifier
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }


    /**
     * @return LegendUrl|null
     */
    public function getLegendurl()
    {
        return $this->legendurl;
    }

    public function setLegendurl(LegendUrl $legendurl)
    {
        $this->legendurl = $legendurl;
        return $this;
    }
}
