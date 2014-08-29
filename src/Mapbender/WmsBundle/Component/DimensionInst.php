<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
class DimensionInst extends Dimension
{

    const SINGLE = 'single';
    const INTERVAL = 'interval';
    const MULTIPLE = 'multiple';
    const MULTIPLEINTERVAL = 'multipleinterval';

    public $type;
    
    public $origextent;
    
    public $use;

    public function getOrigextent()
    {
        return $this->origextent;
    }

    public function setOrigextent($origextent)
    {
        $this->origextent = $origextent;
        return $this;
    }

    public function getUse()
    {
        return $this->use;
    }

    public function setUse($use)
    {
        $this->use = $use;
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

        
    public function findType(){
        $array = explode(",", $this->extent);
        if (count($array) === 0) {
            return null;
        } elseif (count($array) === 1) {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                return self::SINGLE;
            } else {
                return self::INTERVAL;
            }
        } else {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                return self::MULTIPLE;
            } else {
                for ($i = 0; $i < count($array); $i++) {
                    $array[$i] = explode("/", $array[$i]);
                }
                return self::MULTIPLEINTERVAL;
            }
        }
    }
    
    public function getTypedData(){
        $array = explode(",", $this->extent);
        $res = array();
        if (count($array) === 0) {
            return $res;
        } elseif (count($array) === 1) {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                $res[self::SINGLE] = $array[0];
            } else {
                $res[self::INTERVAL] = $help;
            }
        } else {
            $help = explode("/", $array[0]);
            if (count($help) === 1) {
                $res[self::MULTIPLE] = $array;
            } else {
                for ($i = 0; $i < count($array); $i++) {
                    $array[$i] = explode("/", $array[$i]);
                }
                $res[self::MULTIPLEINTERVAL] = $array;
            }
        }
        return $res;
    }
    
}
