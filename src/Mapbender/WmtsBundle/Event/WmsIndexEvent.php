<?php
namespace Mapbender\WmtsBundle\Event;
use Symfony\Component\EventDispatcher\Event;

class WmtsIndexEvent extends Event {

    /**
     * the List of Wmts that was loaded
    */
    protected $wmtsList;

    /**
     * an array of additional columns that should be rendered
    */
    protected $columns;


    public function __construct(){
        $wmtsList = array();
        $columns = array();
    }
    /**
     * 
     * @param type $wmtsList
     */
    public function setWmtsList($wmtsList){
        $this->wmtsList = $wmtsList; 
    }
    
    public function getWmtsList(){
        return $this->wmtsList;
    }

    public function addColumn($name, $data){
        $this->columns[$name] = $data;
    }

    public function getColumns(){
        return $this->columns; 
    }



}

