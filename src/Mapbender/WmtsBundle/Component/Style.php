<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

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
     * @var type
     */
    public $legendurl;

    /**
     * Get isDefault.
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->isDefault;
    }

    /**
     * Set isDefault.
     * @return boolean
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
     * @return \Mapbender\WmtsBundle\Component\Style
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get abstrack
     * @return text
     */
    public function getAbstract()
    {
        return $this->abstract;
    }

    /**
     * Set abstrack
     * @param string $abstract
     * @return \Mapbender\WmtsBundle\Component\Style
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
     * @return \Mapbender\WmtsBundle\Component\Style
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }


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
