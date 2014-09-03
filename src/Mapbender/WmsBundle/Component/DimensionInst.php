<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimensionInst extends Dimension
{

    const TYPE_SINGLE = 'single';
    const TYPE_INTERVAL = 'interval';
    const TYPE_MULTIPLE = 'multiple';
    const TYPE_MULTIPLEINTERVAL = 'multipleinterval';
    
    public $origextent = null;
    
    public $active;
    
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

    public static function getType($extent)
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
    
    public static function getTypedData($extent){
        $array = explode(",", $extent);
        $res = array();
        if (count($array) === 0) {
            return $res;
        } elseif (count($array) === 1) {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                $res[self::TYPE_SINGLE] = $array[0];
            } else {
                $res[self::TYPE_INTERVAL] = $help;
            }
        } else {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                $res[self::TYPE_MULTIPLE] = $array;
            } else {
                for ($i = 0; $i < count($array); $i++) {
                    $array[$i] = explode("/", $array[$i]);
                }
                $res[self::TYPE_MULTIPLEINTERVAL] = $array;
            }
        }
        return $res;
    }
    
}
