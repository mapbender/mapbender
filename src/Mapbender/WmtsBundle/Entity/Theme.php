<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;


/**
 * Theme class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 */
class Theme {
    
    protected $identifier = "";
    
    protected $title = "";
    
    protected $abstract = "";
    
    protected $layerref = "";
    
    protected $theme;
    
    public function __construct(){
        $this->theme = new ArrayCollection();
    }
    
    public function loadFromArray($theme = null, $theme_arr) {
        $theme = $theme=null? $this: $theme;
        $theme->setIdentifier($theme_arr["identifier"]);
        $theme->setTitle($theme_arr["title"]);
        $theme->setAbstract($theme_arr["abstract"]);
        foreach ($theme_arr["theme"] as $subtheme) {
            $theme->addTheme(new Theme(), $subtheme);
        }
        return $theme;
    }
    /**
     * Get identifier
     * @return string identifier
     */
    public function getIdentifier() {
        return $this->identifier;
    }
    /**
     * Set identifier
     * @param string $identifier
     */
    public function setIdentifier($identifier) {
        $this->identifier = $identifier;
    }
    
    /**
     * Get title
     * @return string title
     */
    public function getTitle() {
        return $this->title;
    }
    /**
     * Set title
     * @param string $title
     */
    public function setTitle($title) {
        $this->title = $title;
    }
    
    /**
     * Get abstract
     * @return string abstract 
     */
    public function getAbstract() {
        return $this->abstract;
    }
    /**
     * Set abstract
     * @param string $abstract 
     */
    public function setAbstract($abstract) {
        $this->abstract = $abstract;
    }
    /**
     * Get layerref
     * @return string layerref
     */
    public function getLayerRef() {
        return $this->layerref;
    }
    /**
     * Set layerref
     * @param string $layerref 
     */
    public function setLayerRef($layerref) {
        $this->layerref = $layerref;
    }
    /**
     * Get theme
     * @return arrray theme
     */
    public function getTheme() {
        return $this->theme;
    }
    /**
     * Set theme
     * @param array $theme
     */
    public function setTheme($theme) {
        $this->theme = $theme;
    }
    
    /**
     * Add theme into theme
     * @param Theme $theme 
     */
    public function addTheme($theme) {
        $this->theme->add($theme);
    }
    public function getAsArray() {
        $theme = $this;
        $themes = array();
        $themes["identifier"] = $theme->getIdentifier();
        $themes["title"] = $theme->getTitle();
        $themes["abstract"] = $theme->getAbstract();
        $themes["layerref"] = $theme->getLayerRef();
        $arr = $theme->getTheme();
        foreach ($theme->getTheme() as $subtheme){
//            $themes["theme"][] = $subtheme->getAsArray($subtheme, $subtheme->getTheme());
            $themes["theme"][] = $subtheme->getAsArray();
        }
        return $themes;
    }
}