<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfiguration;
use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;

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
     * @param InstanceConfigurationOptions $options ServiceConfigurationOptions
     * @return $this
     */
    public function setOptions(InstanceConfigurationOptions $options)
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
     * @inheritdoc
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

    /**
     * @inheritdoc
     */
    public static function fromArray($options)
    {
        $ic = null;
        if ($options && is_array($options)) {
            $ic = new WmsInstanceConfiguration();
            if (isset($options['type'])) {
                $ic->type = $options['type'];
            }
            if (isset($options['title'])) {
                $ic->title = $options['title'];
            }
            if (isset($options['isBaseSource'])) {
                $ic->isBaseSource = $options['isBaseSource'];
            }
            if (isset($options['options'])) {
                $ic->options = WmsInstanceConfigurationOptions::fromArray($options['options']);
            }
        }
        return $ic;
    }

}
