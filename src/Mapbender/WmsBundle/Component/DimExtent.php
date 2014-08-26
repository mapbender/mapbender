<?php

namespace Mapbender\WmsBundle\Component;

/**
 * Identifier class.
 *
 * @author Paul Schmidt
 */
abstract class DimExtent
{

    /**
     * ORM\Column(type="string", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $value;
    

    /**
     * Set name
     * 
     * @param mixed $value demension extent
     * @return DimExtent
     */
    public function setValue($value)
    {
        $this->value = $value;
        return $this;
    }

    /**
     * Get name
     * 
     * @return mixed demension extent
     */
    public function getValue()
    {
        return $this->value;
    }

    public static function create($extent)
    {
        $array = explode(",", $extent);
        if(count($array) === 0){
            return null;
        } elseif(count($array) === 1){
            $help = explode("/", $array[0]);
            if(count($help) === 1){
                $d = new DimSingle();
                return $d->setValue($array[0]);
            } else {
                $d = new DimInterval();
                return $d->setValue($help);
            }
        } else {
            $help = explode("/", $array[0]);
            if(count($help) === 1){
                $d = new DimMultiple();
                return $d->setValue($array);
            } else {
                for ($i = 0; $i < count($array); $i++) {
                    $array[$i] = explode("/", $array[$i]);
                }
                $d = new DimMultipleInterval();
                return $d->setValue($array);
            }
        }
    }
    
    public abstract function __toString();

}
