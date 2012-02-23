<?php
namespace Mapbender\WmtsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * WMTSService class
 *
 * @author Paul Schmidt <paul.schmidt@wheregroup.com>
 *
 * 
 * @ORM\Entity
*/
class WmtsInstance {
    /**
    *  @ORM\Id
    *  @ORM\Column(type="integer")
    *  @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;
    /**
     * @ORM\ManyToOne(targetEntity="WmtsService",inversedBy="layer", cascade={"update"})
     * @ORM\JoinColumn(name="wmts_service", referencedColumnName="id")
     */
    protected $wmts_service;
    /**
     * @ORM\Column(type="string", nullable="true")
     */
    private $layersetid = true;
    /**
     * @ORM\Column(type="string", nullable="true")
     */
    private $layerid = true;
    /**
     * @ORM\Column(type="boolean")
     */
    private $visible = true;
    /**
     * @ORM\Column(type="boolean")
     */
    private $proxy = false;
    /**
    * @ORM\Column(type="integer", nullable="true")
    */
    protected $layeridentifier = null;
    /**
     *  @ORM\Column(type="string", nullable="true")
     */
    protected $crs = null;
    /**
     *  @ORM\Column(type="array", nullable="true")
     */
    protected $crsbound = array();
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $style = null;
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $format = null;
    /**
    * @ORM\Column(type="string", nullable="true")
    */
    protected $matrixSet = null;
    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $matrixids = null;
    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $topleftcorner = null;
    /**
    * @ORM\Column(type="array", nullable="true")
    */
    protected $tilesize = null;
    
    
    /**
     * Gets id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }
    
     /**
     * Sets a id
     *
     * @param integer $id
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    
    
    public function getWmts_service(){
        return $this->wmts_service;
    }
    
    public function setWmts_service($wmts_service){
        $this->wmts_service = $wmts_service;
    }
    
    
    
    /**
     * Get the layerid
     *
     * @return string
     */
    public function getLayerid() {
        return $this->layerid;
    }
    /**
     * Set a layerid
     *
     * @param string $layerid
     */
    public function setLayerid($layerid) {
        $this->layerid = $layerid;
    }
    /**
     * Get the layersetid
     *
     * @return string
     */
    public function getLayersetid() {
        return $this->layersetid;
    }
    /**
     * Set a layersetid
     *
     * @param string $layersetid
     */
    public function setLayersetid($layersetid) {
        $this->layersetid = $layersetid;
    }
    
    
    /**
     * Gets visible
     *
     * @return boolean
     */
    public function getVisible() {
        return $this->visible;
    }
    
     /**
     * Sets an visible
     *
     * @param boolean $visible
     */
    public function setVisible($visible) {
        $this->visible = $visible;
    }
    
    /**
     * Gets proxy
     *Layerid
     * @return boolean
     */
    public function getProxy() {
        return $this->proxy;
    }
    /**
     * Sets an proxy
     *
     * @param boolean $proxy
     */
    public function setProxy($proxy) {
        $this->proxy = $proxy;
    }
    
    /**
     * Get the crs
     *
     * @return string
     */
    public function getCrs() {
        return $this->crs;
    }
    /**
     * Set a crs
     *
     * @param string $crs
     */
    public function setCrs($crs) {
        $this->crs = $crs;
    }
    /**
     * Get the crsbound
     *
     * @return array
     */
    public function getCrsbound() {
        return $this->crsbound;
    }
    /**
     * Set a crsbound
     *
     * @param array $crsbound
     */
    public function setCrsbound($crsbound) {
        $this->crsbound = $crsbound;
    }
    
    /**
     * Get the layeridentifier
     *
     * @return string
     */
    public function getLayeridentifier() {
        return $this->layeridentifier;
    }
    /**
     * Set a layeridentifier
     *
     * @param string $layeridentifier
     */
    public function setLayeridentifier($layeridentifier) {
        $this->layeridentifier = $layeridentifier;
    }
    
    /**
     * Get the style
     *
     * @return string
     */
    public function getStyle() {
        return $this->style;
    }
    /**
     * Set a style
     *
     * @param string $style
     */
    public function setStyle($style) {
        $this->style = $style;
    }
    /**
     * Get the format
     *
     * @return string
     */
    public function getFormat() {
        return $this->format;
    }
    /**
     * Set a format
     *
     * @param string $format
     */
    public function setFormat($format) {
        $this->format = $format;
    }
    /**
     * Get the matrixSet
     *
     * @return array
     */
    public function getMatrixSet() {
        return $this->matrixSet;
    }
    /**
     * Set a matrixSet
     *
     * @param array $matrixSet
     */
    public function setMatrixSet($matrixSet) {
        $this->matrixSet = $matrixSet;
    }
    /**
     * Get the matrixids
     *
     * @return array
     */
    public function getMatrixids() {
        return $this->matrixids;
    }
    /**
     * Set a matrixids
     *
     * @param array $matrixids
     */
    public function setMatrixids($matrixids) {
        $this->matrixids = $matrixids;
    }
    /**
     * Get the topleftcorner
     *
     * @return array
     */
    public function getTopleftcorner() {
        return $this->topleftcorner;
    }
    /**
     * Set a topleftcorner
     *
     * @param array $topleftcorner
     */
    public function setTopleftcorner($topleftcorner) {
        $this->topleftcorner = $topleftcorner;
    }
    /**
     * Get the tilesize
     *
     * @return array
     */
    public function getTilesize() {
        return $this->tilesize;
    }
    /**
     * Set a tilesize
     *
     * @param array $tilesize
     */
    public function setTilesize($tilesize) {
        $this->tilesize = $tilesize;
    }
    
    public function save($em){
        if($this->wmts_service !== null){
            foreach($this->wmts_service->getTileMatrixSetAsObjects() as $matrixset){
                if($matrixset->getIdentifier()==$this->matrixSet){
                    $tilesize = array();
                    $topleftcorner = array();
                    $matrixids = array();
                    foreach($matrixset->getTilematrix() as $tilematrix){
                        if(count($tilesize) == 0) {
                            $tilesize[] = $tilematrix->getTilewidth();
                            $tilesize[] = $tilematrix->getTileheight();
                        }
                        if(count($topleftcorner) == 0) {
                            $topleftcorner = explode(" ", $tilematrix->getTopleftcorner());
                        }
                        $matrixids[] = array(
                            "identifier" => $tilematrix->getIdentifier(),
                            "scaleDenominator" => $tilematrix->getScaledenominator());
//                        matrixids:
//                            - { identifier: "0", scaleDenominator: 10000000.0 }
//                        $a=0;
                    }
                    $this->setTilesize($tilesize);
                    $this->setTopleftcorner($topleftcorner);
                    $this->setMatrixids($matrixids);
                    break;
                }
            }
            $crsbound = array();
            foreach($this->wmts_service->getAllLayer() as $layer){
                if($layer->getId()==$this->getLayeridentifier()){
                    $crsbounds = $layer->getCrsBounds();
                    $crsbound = explode(" ", $crsbounds[$this->getCrs()]);
                    break;
                }
            }
            
            $this->setCrsbound($crsbound);
        }
        $em->persist($this);
        $em->flush();
    }
    
    public function completeForm($translator, $form) {
        if( $this->wmts_service !== null){
            $layer_choice = array();
            $layers = array();
            foreach($this->wmts_service->getAllLayer() as $layer){
                $layer_choice[$layer->getId()] = $layer->getTitle();
                $layers[$layer->getId()] = $layer;
            }
            if(count($layer_choice) > 0) {
                $form->add('layersetid', 'text', array(
                    'label' => $translator->trans('layersetid').":"));
                $form->add('layerid', 'text', array(
                    'label' => $translator->trans('layer_id').":"));
                $form->add('layeridentifier', 'choice', array(
                    'label' => $translator->trans('layer').":",
                    'choices' => $layer_choice));
                if($this->layeridentifier !== null || count($layer_choice) == 1) {
                    if($this->layeridentifier !== null) {
                        $layer = $layers[$this->layeridentifier];
                    }else{
                        $keys = array_keys($layer_choice);
                        $layer = $layers[$keys[0]];
                    }
                    
                    $form->add('visible', 'checkbox', array(
                        'label' => $translator->trans('visible').":",
                        'required'  => false));
                    
                    $form->add('proxy', 'checkbox', array(
                        'label' => $translator->trans('proxy').":",
                        'required'  => false));
                    $help_arr = array();
                    foreach($layer->getCrs() as $crs){
                        $help_arr[$crs] = $crs;
                    }
                    $form->add('crs', 'choice', array(
                        'label' => $translator->trans('crs').":",
                        'choices' => $help_arr));
                    unset($help_arr);
                    $help_arr = array();
                    foreach( $layer->getStyles() as $style){
                        $help_arr[$style["identifier"]] = $style["title"];
                    }
                    $form->add('style', 'choice', array(
                        'label' => $translator->trans('style').":",
                        'choices' => $help_arr));
                    unset($help_arr);
                    $help_arr = array();
                    foreach( $layer->getRequestDataFormats() as $format){
                        $help_arr[$format] = $format;
                    }
                    $form->add('format', 'choice', array(
                        'label' => $translator->trans('format').":",
                        'choices' => $help_arr));
                    unset($help_arr);
                    $help_arr = array();
                    foreach( $layer->getTileMatrixSetLink() as $link){
                        $help_arr[$link] = $link;
                    }
                    $form->add('matrixSet', 'choice', array(
                        'label' => $translator->trans('matrixSet').":",
                        'choices' => $help_arr));
                    unset($help_arr);
                }
            }
        }
        return $form;
    }

}
?>
