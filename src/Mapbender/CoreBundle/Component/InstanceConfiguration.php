<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Component\Base\ConfigurationBase;
use Mapbender\WmsBundle\Component\WmsInstanceConfiguration;

/**
 * Description of SourceConfiguration
 *
 * @author Paul Schmidt
 */
abstract class InstanceConfiguration extends ConfigurationBase
{
    /**
     * ORM\Column(type="string", nullable=true)
     * @var string
     */
    public $type;

    /**
     * ORM\Column(type="string", nullable=ture)
     * @var string
     */
    public $title;

    /**
     * ORM\Column(type="text", nullable=true)
     * @var InstanceConfigurationOptions|array
     */
    public $options;

    /**
     * ORM\Column(type="text", nullable=false)
     */
    public $children;
    
    /**
     * ORM\Column(type="boolean", nullable=false)
     */
    public $isBaseSource = true;

    /**
     * InstanceConfiguration constructor.
     */
    public function __construct()
    {
        $this->options = array();
        $this->children = array();
    }

    /**
     * Sets a type
     *
     * @param string $type
     * @return $this
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
     * @return string
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
    
}

