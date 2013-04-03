<?php
namespace Mapbender\WmsBundle\Event;
use Symfony\Component\EventDispatcher\Event;

class WmsIndexEvent extends Event {

    /**
     * the List of Wms that was loaded
    */
    protected $wmsList;

    /**
     * an array of additional columns that should be rendered
    */
    protected $columns;


    public function __construct(){
        $wmsList = array();
        $columns = array();
    }
    /**
     * 
     * @param type $wmsList
     */
    public function setWmsList($wmsList){
        $this->wmsList = $wmsList; 
    }
    
    public function getWmsList(){
        return $this->wmsList;
    }

    public function addColumn($name, $data){
        $this->columns[$name] = $data;
    }

    public function getColumns(){
        return $this->columns; 
    }



}

