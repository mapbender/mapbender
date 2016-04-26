<?php

namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfiguration;
use Mapbender\WmsBundle\Component\WmsInstanceConfiguration;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of SourceConfiguration
 *
 * @author Paul Schmidt
 */
abstract class InstanceConfiguration
{

    /**
     * ORM\Column(type="string", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $type;

    /**
     * ORM\Column(type="string", nullable=ture)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $title;

    /**
     * ORM\Column(type="text", nullable=true)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $options;

    /**
     * ORM\Column(type="text", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $children;
    
    /**
     * ORM\Column(type="boolean", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $isBaseSource = true;

    public function __construct()
    {
        $this->options = array();
        $this->children = array();
    }

    /**
     * Sets a type
     * 
     * @return SierviceConfiguration 
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * Returns a type
     * 
     * @return string type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Sets a title
     * 
     * @param string $title title
     * @return InstanceConfiguration 
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Returns a title
     * 
     * @return string title
     */
    public function getTitle()
    {
        return $this->title;
    }

    
    
    /**
     * Sets a isBaseSource
     * 
     * @param boolean $isBaseSource isBaseSource
     * @return InstanceConfiguration 
     */
    public function setIsBaseSource($isBaseSource)
    {
        $this->isBaseSource = $isBaseSource;
        return $this;
    }

    /**
     * Returns a isBaseSource
     * 
     * @return boolean isBaseSource
     */
    public function getIsBaseSource()
    {
        return $this->isBaseSource;
    }
    
    /**
     * Sets options
     * 
     * @param InstanceConfigurationOptions $options ServiceConfigurationOptions
     * @return InstanceConfiguration 
     */
    public abstract function setOptions(InstanceConfigurationOptions $options);

    /**
     * Returns options
     * 
     * @return ServiceConfigurationOptions
     */
    public abstract function getOptions();

    /**
     * Sets a children
     * 
     * @param array $children children
     * @return InstanceConfiguration 
     */
    public abstract function setChildren($children);

    /**
     * Returns a title
     * 
     * @return integer children
     */
    public abstract function getChildren();
    
    /**
     * Returns InstanceConfiguration as array
     * 
     * @return array
     */
    public abstract function toArray();
    
    /**
     * Creates an InstanceConfiguration from options
     * 
     * @param array $options array with options
     * @return InstanceConfiguration
     */
    public static function fromArray($options)
    {
        if($options && is_array($options))
        {
            if(isset($options['type']) && $options['type'] === 'wms'){
                return WmsInstanceConfiguration::fromArray($options);
            }
        }
        return null;
    }

}

