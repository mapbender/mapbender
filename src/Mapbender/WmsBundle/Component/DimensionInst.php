<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimensionInst extends Dimension
{
    const TYPE_SINGLE           = 'single';
    const TYPE_INTERVAL         = 'interval';
    const TYPE_MULTIPLE         = 'multiple';
    const TYPE_MULTIPLEINTERVAL = 'multipleinterval';

    public $origextent = null;
    public $active;
    public $type;

    public function getCreater()
    {
        return $this->creater;
    }

    public function setCreater($creater)
    {
        $this->creater = $creater;
        return $this;
    }

    public function getOrigextent()
    {
        return $this->origextent;
    }

    public function setOrigextent($origextent)
    {
        $this->origextent = $origextent;
        return $this;
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
        $res   = array();

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
            'origextent' => $this->getData($this->getOrigextent()),
            'type' => $this->getType(),
        );
    }

    /**
     * Factory method, copies attributes from given Dimension object.
     * Adds Origextent initially equal to Dimension Extent
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
        $diminst->setOrigextent($dim->getExtent());
        $diminst->setExtent($dim->getExtent());
        $diminst->setType(static::findType($dim->getExtent()));
        return $diminst;
    }
}
