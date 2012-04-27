<?php
namespace Mapbender\WmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * WmsInstanceLayer class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 *
 * 
 * @ORM\Entity
*/
class WmsInstanceLayer {
    /**
    * @ORM\Id
    * @ORM\Column(type="string", nullable="false")
    */
    protected $name;
    /**
     * @ORM\Column(type="integer", nullable="false")
     */
    protected $instanceid = -1;
    /**
     * @ORM\Column(type="integer", nullable="false")
     */
    protected $layerid = -1;
    /**
     * @ORM\Column(type="string", nullable="true")
     */
    protected $title;
//    /**
//     * 
//     * @ORM\Column(type="boolean", nullable="false")
//     */
//    protected $published = false;
    /**
     * @ORM\Column(type="boolean", nullable="false")
     */
    protected $visible = false;
    /**
    * @ORM\Column(type="boolean", nullable="false")
    */
    protected $queryable = false;
    
    public function __construct() {
    }
    
//    public static function create($instanceid, $layerid,
//            $name, $title, $published, $visible, $queryable) {
    public static function create($instanceid, $layerid,
            $name, $title, $visible, $queryable) {
        $wmsInstanceLayer = new WmsInstanceLayer();
        $wmsInstanceLayer->instanceid = $instanceid;
        $wmsInstanceLayer->layerid = $layerid;
        $wmsInstanceLayer->name = $name;
        $wmsInstanceLayer->title = $title;
//        $wmsInstanceLayer->published = $published;
        $wmsInstanceLayer->visible = $visible;
        $wmsInstanceLayer->queryable = $queryable;
        return $wmsInstanceLayer;
    }
    
    public static function createFromFullArray($array){
        if($array !== null) {
            return WmsInstanceLayer::create(
                    isset($array['instanceid'])? $array['instanceid'] : "",
                    isset($array['layerid'])? $array['layerid'] : "",
                    isset($array['name'])? $array['name'] : "",
                    isset($array['title'])? $array['title'] : "",
//                    isset($array['published'])? $array['published'] : false,
                    isset($array['visible'])? $array['visible'] : false,
                    isset($array['queryable'])? $array['queryable'] : null);
        } else {
            return null;
        }
    }
    public function getAsArray(){
        $array =  array(
            'name' => $this->name,
            'title' => $this->title,
//            'published' => $this->published,
            'visible' => $this->visible);
        if($this->queryable !== null){
            $array['queryable'] = $this->queryable;
        }
        return $array;
    }
    
    public function getAsFullArray(){
        return array(
            'instanceid' => $this->instanceid,
            'layerid' => $this->layerid,
            'name' => $this->name,
            'title' => $this->title,
//            'published' => $this->published,
            'visible' => $this->visible,
            'queryable' => $this->queryable);
    }
    
    public function setInstanceid($val){
        $this->instanceid = $val;
    }
    
    public function getInstanceid(){
        return $this->instanceid;
    }
    
    public function setlayerid($val){
        $this->ilayerid = $val;
    }
    
    public function getLayerid(){
        return $this->layerid;
    }
    
    public function setName($val){
        $this->name = $val;
    }
    
    public function getName(){
        return $this->name;
    }
    
    public function setTitle($val){
        $this->title = $val;
    }
    
    public function getTitle(){
        return $this->title;
    }
//    
//    public function setPublished($val){
//        if($val === null) {
//            $this->published = false;
//        } else if(is_bool($val)) {
//            $this->published = $val;
//        } else {
//            $this->published = false;
//        }
//    }
//    
//    public function getPublished(){
//        return $this->published;
//    }
    
    public function setVisible($val){
        if($val === null) {
            $this->visible = false;
        } else if(is_bool($val)) {
            $this->visible = $val;
        } else {
            $this->visible = false;
        }
    }
    
    public function getVisible(){
        return $this->visible;
    }
    
    public function setQueryable($val){
        if($val === null) {
            $this->queryable = null;
        } else if(is_bool($val)) {
            $this->queryable = $val;
        } else {
            $this->queryable = null;
        }
    }
    
    public function getQueryable(){
        return $this->queryable;
    }
    
    public function completeForm($translator, $form, $read_only = false) {
        $form->add('instanceid', 'hidden', array(
            'data' => $this->instanceid,
            'required'  => true));
        $form->add('layerid', 'hidden', array(
            'data' => $this->layerid,
            'required'  => true));
//        $form->add('published', 'checkbox', array(
//            'label' => $translator->trans('published'),
//            'required'  => false,
//            "read_only" => $read_only ? true : false));
        $form->add('name', 'text', array(
            'attr' => array('readonly' => 'readonly'),
            'required'  => true,
            'label' => $translator->trans('Name').":"));
        $form->add('title', 'text', array(
            'attr' => array('readonly' => 'readonly'),
            'required'  => true,
            'label' => $translator->trans('Title').":"));
        $form->add('visible', 'checkbox', array(
            'label' => $translator->trans('visible').":",
            'required'  => false,
            "read_only" => $read_only ? true : false));
        if($this->queryable !== null) {
            $form->add('queryable', 'checkbox', array(
                'label' => $translator->trans('queryable').":",
                'required'  => false,
                "read_only" => $read_only ? true : false));
        }
        return $form;
    }

}
