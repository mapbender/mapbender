<?php
namespace Mapbender\WmtsBundle\Entity;
use Doctrine\ORM\Mapping as ORM;
use Mapbender\WmtsBundle\Entity\GroupLayer;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity
*/
class WMTSLayer extends GroupLayer {

    /**
     * @ORM\Column(type="boolean", nullable="false")
     */
    protected $queryable = false;
    /**
      * @ORM\Column(type="array", nullable="true")
     */
    protected $styles = array();
    /**
     * @ORM\Column(type="array", nullable="true")
     */
    protected $crs = array();
    /**
     * @ORM\Column(type="string", nullable="true")
     */
    protected $latLonBounds = "-180 -90 180 90";
    /**
     * @ORM\Column(type="array", nullable="true")
     */
    protected $tileMatrixSetLink = array();
    /**
     * @ORM\Column(type="array", nullable="true")
     */
    protected $metadataURL = array();
    /**
     * @ORM\Column(type="array", nullable="true")
     */
    protected $resourceUrl = array();

    /**
     * @ORM\Column(type="array", nullable="true")
     */
    protected $requestDataFormats = array();
    /**
     * @ORM\Column(type="array", nullable="true")
     */
    protected $requestInfoFormats = array();
    /**
     * Create an object WMTSLayer
     */
    public function __construct()
    {
//        $this->queryable    = false;
//        $this->layer        = new ArrayCollection();
//        $this->tileMatrixSetLink = new ArrayCollection();
    }
    /**
     * returns the WMTSService a WMTSLayer belongs to. This is neccessary because WMTSLayer::getParent() might return a GroupLayer only
     */
    public function getWMTS(){
        $layer = $this;
        // go up until layer becomes falsy
        $parent = $layer->getParent();
        while($parent != null){
            $layer = $parent;
            $parent = $layer->getParent();
        }
        return $layer;
    }
    /**
     * Set crs
     * @param $crs 
     */
    public function setCrs($crs){
        $this->crs = $crs;
    }
    /**
     * Get crs
     * @return array crs
     */
    public function getCrs(){
        return $this->crs;
    }
    /**
     * Get default crs
     * @return string 
     */
    public function getDefaultCrs(){
        $crs = explode(',',$this->crs);
        return $crs[0] ?:"";
    }
    /**
     * Set latLonBounds
     * @param $bounds 
     */
    public function setLatLonBounds($bounds){
        $this->latLonBounds = $bounds; 
    }
    /**
     * Get latLonBounds
     * @return string latLonBounds
     */
    public function getLatLonBounds(){
        return $this->latLonBounds;
    }

    
    /**
     * Set queryable
     *
     * @param boolean $queryable
     */
    public function setQueryable($queryable)
    {
        $this->queryable = $queryable;
    }

    /**
     * Get queryable
     *
     * @return boolean queryable
     */
    public function getQueryable()
    {
        return $this->queryable;
    }

    /**
     * Set metadataURL
     *
     * @param string $metadataURL
     */
    public function setMetadataURL($metadataURL)
    {
        $this->metadataURL = $metadataURL;
    }

    /**
     * Get metadataURL
     *
     * @return array  metadataURL
     */
    public function getMetadataURL()
    {
        return $this->metadataURL;
    }
    
    /**
     * Set resourceUrl
     *
     * @param array $resourceUrl
     */
    public function setResourceUrl($resourceUrl)
    {
        $this->resourceUrl = $resourceUrl;
    }

    /**
     * Get resourceUrl
     *
     * @return array resourceUrl
     */
    public function getResourceUrl()
    {
        return $this->resourceUrl;
    }

    /**
     * Set requestDataFormats
     *
     * @param array $requestDataFormats
     */
    public function setRequestDataFormats($requestDataFormats)
    {
        $this->requestDataFormats = $requestDataFormats;
    }

    /**
     * Get requestDataFormats
     *
     * @return array requestDataFormats
     */
    public function getRequestDataFormats()
    {
        return $this->requestDataFormats;
    }
    /**
     * Set requestInfoFormats
     *
     * @param array $requestInfoFormats
     */
    public function setRequestInfoFormats($requestInfoFormats)
    {
        if($requestInfoFormats!=null && count($requestInfoFormats)>0){
            $this->setQueryable(true);
        }
        $this->requestInfoFormats = $requestInfoFormats;
    }

    /**
     * Get requestInfoFormats
     *
     * @return array requestInfoFormats
     */
    public function getRequestInfoFormats()
    {
        return $this->requestInfoFormats;
    }
    /**
     * Get styles
     * @return array styles
     */
    public function getStyles(){
        return $this->styles ;
    }
    /**
     * Set styles
     * @param array $styles 
     */
    public function setStyles($styles ){
        $this->styles  = $styles ;
    }
    /**
     * Add style
     * @param array $style 
     */
    public function addStyle($style){
        $this->styles[]  = $style ;
    }
    
    /**
     * Add tileMatrixSetLink
     * @param array $tileMatrixSetLink 
     */
    public function addTileMatrixSetLink($tileMatrixSetLink){
        $this->tileMatrixSetLink->add($tileMatrixSetLink);
    }
    /**
     * Set tileMatrixSetLink
     * @param $tileMatrixSetLinks 
     */
    public function setTileMatrixSetLink($tileMatrixSetLinks){
        $this->tileMatrixSetLink = $tileMatrixSetLinks;
    }
    /**
     * Get tileMatrixSetLink
     * @return array tileMatrixSetLink
     */
    public function getTileMatrixSetLink(){
        return $this->tileMatrixSetLink;
    }
    
    
    /**
     * Get layer
     * @return array layer
     */
    public function getLayer(){
        return $this->layer;
    }
//
//        #WORKAROUND: form some reason $layer is an array of arrays instead of an array of WMTSLayerobjects
//
//        $this->tileMatrixSet = new ArrayCollection();
////        $newLayer = null;
//        foreach ($tileMatrixSets as $tms ){
////            $newLayer = new WMTSLayer(); 
////            $newLayer->setName($l['name']);
////            $newLayer->setTitle($l['title']);
////            $newLayer->setAbstract($l['abstract']);
////            $this->layer->add($newLayer);
//        }
//
//    }

}
