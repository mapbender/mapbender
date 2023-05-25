<?php

namespace Mapbender\WmsBundle\Component;

/**
 * @author Paul Schmidt
 */
class DimensionInst extends Dimension
{
    const TYPE_SINGLE = 'single';
    const TYPE_INTERVAL = 'interval';
    const TYPE_MULTIPLE = 'multiple';
    const TYPE_MULTIPLEINTERVAL = 'multipleinterval';

    public $active;
    public $type;
    public $id;

    public function __unserialize(array $array)
    {
        foreach (['active', 'type', 'id', 'name', 'units', 'unitSymbol', 'default', 'multipleValues', 'nearestValue', 'current', 'extent'] as $key) {
            if (array_key_exists($key, $array)) $this->$key = $array[$key];
        }
    }

    public function getActive()
    {
        return $this->active;
    }

    public function setActive($active)
    {
        $this->active = $active;
        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public static function findType($extent)
    {
        $array = explode(",", $extent);
        if (count($array) === 0) {
            return null;
        } elseif (count($array) === 1) {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                return self::TYPE_SINGLE;
            } else {
                return self::TYPE_INTERVAL;
            }
        } else {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                return self::TYPE_MULTIPLE;
            } else {
                return self::TYPE_MULTIPLEINTERVAL;
            }
        }
    }

    public static function getData($extent)
    {
        $array = is_string($extent) ? explode(",", $extent) : $extent;
        $res = array();

        if (!$extent) {
            return $res;
        }

        if (count($array) === 1) {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                $res = self::getValidValue($array[0]);
            } else {
                foreach ($help as $value) {
                    $res[] = self::getValidValue($value);
                }
            }
        } else {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                foreach ($array as $value) {
                    $res[] = self::getValidValue($value);
                }
            } else {
                for ($i = 0; $i < count($array); $i++) {
                    $res[$i] = array();
                    foreach (explode("/", $array[$i]) as $value) {
                        $res[$i][] = self::getValidValue($value);
                    }
                }
            }
        }
        return $res;
    }

    private static function getValidValue($value)
    {
        if (is_numeric($value) && floatval($value) === floatval(intval($value))) {
            return intval($value);
        } elseif (is_numeric($value)) {
            return floatval($value);
        } else {
            return $value;
        }
    }

    public function getConfiguration()
    {
        return array(
            'current' => $this->getCurrent(),
            'default' => $this->getDefault(),
            'multipleValues' => $this->getMultipleValues(),
            'name' => $this->getName(),
            '__name' => $this->getParameterName(),
            'nearestValue' => $this->getNearestValue(),
            'unitSymbol' => $this->getUnitSymbol(),
            'units' => $this->getUnits(),
            'extent' => $this->getData($this->getExtent()),
            'type' => $this->getType(),
        );
    }

    public static function fromConfiguration(array $config)
    {
        $inst = new static();
        $inst->current = $config['current'];
        $inst->default = $config['default'];
        $inst->multipleValues = $config['multipleValues'];
        $inst->name = $config['name'];
        $inst->nearestValue = $config['nearestValue'];
        $inst->unitSymbol = $config['unitSymbol'];
        $inst->units = $config['units'];
        $inst->extent = $config['extent'];
        $inst->type = $config['type'];
        return $inst;
    }

    /**
     * Factory method, copies attributes from given Dimension object.
     * Adds Active initially false
     * Adds Type found from Dimension Extent via @see findType
     *
     * @param Dimension $dim
     * @return static
     */
    public static function fromDimension(Dimension $dim)
    {
        $diminst = new static();
        $diminst->setCurrent($dim->getCurrent());
        $diminst->setDefault($dim->getDefault());
        $diminst->setMultipleValues($dim->getMultipleValues());
        $diminst->setName($dim->getName());
        $diminst->setNearestValue($dim->getNearestValue());
        $diminst->setUnitSymbol($dim->getUnitSymbol());
        $diminst->setUnits($dim->getUnits());
        $diminst->setActive(false);
        $diminst->setExtent($dim->getExtent());
        $diminst->setType(static::findType($dim->getExtent()));
        return $diminst;
    }
}
