<?php

namespace Mapbender\WmsBundle\Component;

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Size
 *
 * @author Paul Schmidt
 */
class Size
{
    /**
     * ORM\Column(type="integer", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $width = 0;

    /**
     * ORM\Column(type="integer", nullable=false)
     */
    //@TODO Doctrine bug: "protected" replaced with "public"
    public $height = 0;
    
    /**
     * Sets a width
     * 
     * @return Size 
     */
    public function setWidth($width){
        $this->width = $width;
        return $this;
    }
    
    /**
     * Returns a width
     * 
     * @return integer width
     */
    public function getWidth(){
        return $this->width;
    }
    
    /**
     * Sets a height
     * 
     * @return Size 
     */
    public function setHeight($height){
        $this->height = $height;
        return $this;
    }
    
    /**
     * Returns a height
     * 
     * @return integer height
     */
    public function getHeight(){
        return $this->height;
    }
}

?>
