<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfiguration;
use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 *
 * @author Paul Schmidt
 *
 * @deprecated this entire class is only used transiently to capture values via its setters, then converted to
 *     array and discared. The sanitization performed along the way is minimal. The ONLY remaining usage is in
 *     WmcParser110.
 *
 * @see WmcParser110::parseLayer()
 * @internal
 *
 * @property WmsInstanceConfigurationOptions|array $options
 *
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
     * @return WmsInstanceConfigurationOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     *
     * @param array $children
     * @return InstanceConfiguration 
     */
    public function setChildren($children)
    {
        $this->children = $children;
        return $this;
    }

    /**
     *
     * @return array children
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
        if (is_array($this->options)) {
            $optionsArray = $this->options;
        } else {
            $optionsArray = $this->options->toArray();
        }
        return array(
            "type" => $this->type,
            "title" => $this->title,
            "isBaseSource" => $this->isBaseSource,
            "options" => $optionsArray,
            "children" => $this->children
        );
    }

    /**
     * @param WmsInstance $instance
     * @param bool $strict
     * @return null|static
     */
    public static function fromEntity(WmsInstance $instance, $strict = true)
    {
        $options = array(
            'type' => strtolower($instance->getType()),
            'title' => $instance->getTitle(),
            'isBaseSource' => $instance->isBaseSource(),
            'options' => WmsInstanceConfigurationOptions::fromEntity($instance),
        );
        return static::fromArray($options, $strict);
    }

    /**
     * Helper method that converts an entity to its array representation
     * @todo: this probably belongs directly in a frontend config generating service
     *
     * @param WmsInstance $entity
     * @return array
     */
    public static function entityToArray($entity)
    {
        return static::fromEntity($entity)->toArray();
    }
}
