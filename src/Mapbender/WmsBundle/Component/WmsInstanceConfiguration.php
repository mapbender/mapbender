<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfiguration;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of WmsInstanceConfiguration
 *
 * @author Paul Schmidt
 */
class WmsInstanceConfiguration extends InstanceConfiguration
{

    /**
     * Sets options
     * 
     * @param ServiceConfigurationOptions $options ServiceConfigurationOptions
     * @return InstanceConfiguration 
     */
    public function setOptions($options)
    {
        $this->options = $options;
        return $this;
    }

    /**
     * Returns options
     * 
     * @return ServiceConfigurationOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets a children
     * 
     * @param array $children children
     * @return InstanceConfiguration 
     */
    public function setChildren($children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     * Returns a title
     * 
     * @return integer children
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * 
     * @return array
     */
    public function toArray()
    {
        return array(
            "type" => $this->type,
            "title" => $this->title,
            "isBaseSource" => $this->isBaseSource,
            "options" => $this->options->toArray(),
            "children" => $this->children
        );
    }

}
?>
