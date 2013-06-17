<?php

namespace Mapbender\CoreBundle\Component;

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
     * 
     * @param type $width width
     * @param type $height height
     */
    public function __construct($width = null, $height = null)
    {
        $this->width = $width;
        $this->height = $height;
    }
    
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
    
    /**
     * Creates a Size from parameters (array("width"=>xx,"height"=>yy))
     * 
     * @param array $parameters
     * @return Size
     */
    public static function create($parameters = array()){
        return new Size(
                isset($parameters["width"]) ? $parameters["width"] : null,
                isset($parameters["height"]) ? $parameters["height"] : null);
    }
    
    /**
     * Returns a Size as an array
     * 
     * @return array
     */
    public function toArray(){
        return array("width" => $this->width, "height" => $this->height);
    }
}

?>
