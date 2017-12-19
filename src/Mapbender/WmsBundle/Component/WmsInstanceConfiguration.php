<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\InstanceConfiguration;
use Mapbender\CoreBundle\Component\InstanceConfigurationOptions;
use Mapbender\WmsBundle\Entity\WmsInstance;

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
     * @return InstanceConfigurationOptions
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

    /**
     * Factory method that populates new instance from given entity
     *
     * @param WmsInstance $entity
     * @return static
     */
    public static function fromEntity($entity)
    {
        $wmsconf = new static();

        $wmsconf->setType(strtolower($entity->getType()));
        $wmsconf->setTitle($entity->getTitle());
        $wmsconf->setIsBaseSource($entity->isBasesource());
        $options = WmsInstanceConfigurationOptions::fromEntity($entity);
        $wmsconf->setOptions($options);

        return $wmsconf;
    }

    /**
     * Helper method that converts an entity to its array representation
     * @todo: this probably belongs directly in the entity
     *
     * @param WmsInstance $entity
     * @return array
     */
    public static function entityToArray($entity)
    {
        return static::fromEntity($entity)->toArray();
    }
}
