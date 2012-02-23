<?php

namespace Mapbender\WmtsBundle\Component;
use Mapbender\WmtsBundle\Entity\WMTSService;
use Mapbender\WmtsBundle\Entity\WMTSLayer;
use Mapbender\WmtsBundle\Entity\Layer;
use Mapbender\WmtsBundle\Entity\GroupLayer;
use Mapbender\WmtsBundle\Entity\Theme;
use Mapbender\WmtsBundle\Entity\TileMatrix;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Component\Exception\ParsingException;

/**
* Class that Parses WMTS GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
* Parses WMTS GetCapabilities documents
*/
class CapabilitiesParser {

    /**
     * The XML representation of the Capabilites Document
     * @var DOMDocument
     */
    protected $doc;
    
    protected $xpath;
    
    /**
    * @param DOMDocument the document to be parsed
    */
    public function __construct($data){

        $this->doc = new \DOMDocument();
        if(!$this->doc->loadXML($data)){
            if(!$this->doc->loadHTML($data)){
                  throw new \UnexpectedValueException("Could not parse CapabilitiesDocument.");
            }
        }
        $this->xpath = new \DOMXPath($this->doc);
        $this->registerNamespace();
//        if($this->doc->documentElement->tagName == "ServiceExceptionReport"){
//            $message=$this->doc->documentElement->nodeValue;
//            throw new  ParsingException($message);
//        
//        }
//
//        $version = $this->doc->documentElement->getAttribute("version");
//        switch($version){
//
//            case "1.0.0":
//            case "1.1.0":
//            case "1.1.1":
//            case "1.3.0":
//            default:
//            break;
//
//        }

        if(!@$this->doc->validate()){
            // TODO logging
        };
    }
    
    private function registerNamespace() {
        // TODO load namespace dynamic from root element
        $this->xpath->registerNamespace('wmts', "http://www.opengis.net/wmts/1.0"); 
        $this->xpath->registerNamespace('ows', "http://www.opengis.net/ows/1.1"); 
        $this->xpath->registerNamespace('xlink', "http://www.w3.org/1999/xlink"); 
        $this->xpath->registerNamespace('xsi', "http://www.w3.org/2001/XMLSchema-instance"); 
        $this->xpath->registerNamespace('gml', "http://www.opengis.net/gml");
    }
    
    private function getValue($xpath, $contextElm){
        try {
            $elm = $this->xpath->query($xpath, $contextElm)->item(0);
            if($elm->nodeType == XML_ELEMENT_NODE) {
                return $elm->wholeText;
            } else if($elm->nodeType == XML_ATTRIBUTE_NODE) {
                return $elm->value;
            } else if($elm->nodeType == XML_TEXT_NODE){
                return $elm->wholeText;
            } else {
                return null;
            }
        }catch(\Exception $E){
            return null;
        }
    }

    /**
    *   @return WMTSService
    */
    public function getWMTSService(){
        $wmts = new WMTSService();
        
        $root = $this->doc->documentElement;
        $wmts->setVersion($this->getValue("./@version", $root));
        $wmts->setIdentifier("WMTS"); //TODO ???
        //
        // read ServiceIdentification
        $serviceIdentification = $this->xpath->query("./ows:ServiceIdentification", $root)->item(0);
        if($serviceIdentification != null){
            $wmts->setTitle($this->getValue("./ows:Title/text()", $serviceIdentification));
            $wmts->setAbstract($this->getValue("./ows:Abstract/text()", $serviceIdentification));
            $wmts->setFees($this->getValue("./ows:Fees/text()", $serviceIdentification));
            $wmts->setAccessConstraints($this->getValue("./ows:AccessConstraints/text()", $serviceIdentification));
            $wmts->setServiceType($this->getValue("./ows:ServiceType/text()", $serviceIdentification));
        }
        unset($serviceIdentification);
        // read ServiceProvider 
        $serviceProvider = $this->xpath->query("./ows:ServiceProvider", $root)->item(0);
        if($serviceProvider != null){
            $wmts->setServiceProviderName($this->getValue("./ows:ProviderName/text()", $serviceProvider));
            $wmts->setServiceProviderSite($this->getValue("./ows:ProviderSite/text()", $serviceProvider));
            $wmts->setContactIndividualName($this->getValue("./ows:ServiceContact/ows:IndividualName/text()", $serviceProvider));
            $wmts->setContactPositionName($this->getValue("./ows:ServiceContact/ows:PositionName/text()", $serviceProvider));
            $wmts->setContactPhoneVoice($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Voice/text()", $serviceProvider));
            $wmts->setContactPhoneFacsimile($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Phone/ows:Facsimile/text()", $serviceProvider));
            $wmts->setContactAddressDeliveryPoint($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:DeliveryPoint/text()", $serviceProvider));
            $wmts->setContactAddressCity($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:City/text()", $serviceProvider));
            $wmts->setContactAddressAdministrativeArea($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:AdministrativeArea/text()", $serviceProvider));
            $wmts->setContactAddressPostalCode($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:PostalCode/text()", $serviceProvider));
            $wmts->setContactAddressCountry($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:Country/text()", $serviceProvider));
            $wmts->setContactElectronicMailAddress($this->getValue("./ows:ServiceContact/ows:ContactInfo/ows:Address/ows:ElectronicMailAddress/text()", $serviceProvider));
        }
        unset($serviceProvider);
        // read OperationsMetadata 
        $operationsMetadata = $this->xpath->query("./ows:OperationsMetadata", $root)->item(0);
        if($operationsMetadata != null){
            $getCapabilities = $this->xpath->query("./ows:Operation[@name='GetCapabilities']", $operationsMetadata)->item(0);
            if($getCapabilities != null){
                $getrest = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[./ows:Constraint/ows:AllowedValues/ows:Value/text()='RESTful']/@xlink:href", $getCapabilities);
                $wmts->setRequestGetCapabilitiesGETREST($getrest);
                $getkvp = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[./ows:Constraint/ows:AllowedValues/ows:Value/text()='KVP']/@xlink:href", $getCapabilities);
                $wmts->setRequestGetCapabilitiesGETKVP($getkvp);
//                $postsoap = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[/ows:Constraint/ows:AllowedValues/ows:Value/text()='SOAP']/@xlink:href", $getCapabilities);
//                $wmts->setRequestGetCapabilitiesPOSTSOAP($postsoap);
            }
            unset($getCapabilities);
            $getTile = $this->xpath->query("./ows:Operation[@name='GetTile']", $operationsMetadata)->item(0);
            if($getTile != null){
                $getrest = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[./ows:Constraint/ows:AllowedValues/ows:Value/text()='RESTful']/@xlink:href", $getTile);
                $wmts->setRequestGetTileGETREST($getrest);
                $getkvp = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[./ows:Constraint/ows:AllowedValues/ows:Value/text()='KVP']/@xlink:href", $getTile);
                $wmts->setRequestGetTileGETKVP($getkvp);
//                $postsoap = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[/ows:Constraint/ows:AllowedValues/ows:Value/text()='SOAP']/@xlink:href/text()", $getTile);
//                $wmts->setRequestGetTilePOSTSOAP($postsoap);
            }
            unset($getTile);
            $getFeatureInfo = $this->xpath->query("./ows:Operation[@name='GetFeatureInfo']", $operationsMetadata)->item(0);
            if($getFeatureInfo != null){
                $getrest = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[/ows:Constraint/ows:AllowedValues/ows:Value/text()='RESTful']/@xlink:href", $getFeatureInfo);
                $wmts->setRequestGetFeatureInfoGETREST($getrest);
                $getkvp = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[/ows:Constraint/ows:AllowedValues/ows:Value/text()='KVP']/@xlink:href", $getFeatureInfo);
                $wmts->setRequestGetFeatureInfoGETKVP($getkvp);
//                $postsoap = $this->getValue("./ows:DCP/ows:HTTP/ows:Get[/ows:Constraint/ows:AllowedValues/ows:Value/text()='SOAP']/@xlink:href", $getFeatureInfo);
//                $wmts->setRequestGetFeatureInfoPOSTSOAP($postsoap);
            }
            unset($getFeatureInfo);
        }
        unset($operationsMetadata);
        
        // read Contents 
        $contents = $this->xpath->query("./wmts:Contents", $root)->item(0);
        if($contents != null){
            $layerlist = $this->xpath->query("./wmts:Layer", $contents);
            foreach($layerlist as $layerEl) {
                $layer = new WMTSLayer();
//                $layer->setName($node->nodeValue); ???
                $layer->setTitle($this->getValue("./ows:Title/text()", $layerEl));
                $layer->setAbstract($this->getValue("./ows:Abstract/text()", $layerEl));
                $crs = array();
                $bboxesEl = $this->xpath->query("./ows:BoundingBox", $layerEl);
                foreach($bboxesEl as $bboxEl) {
                    $crs[] = $this->getValue("./@crs", $bboxEl);
                }
                $layer->setCRS(implode(",", $crs));
                
                $crs84 = $this->getValue("./ows:WGS84BoundingBox/ows:LowerCorner/text()", $layerEl)
                        ." ". $this->getValue("./ows:WGS84BoundingBox/ows:UpperCorner/text()", $layerEl);
                $layer->setLatLonBounds($crs84);
                
                $layer->setIdentifier($this->getValue("./ows:Identifier/text()", $layerEl));
                
                $metadataUrlsEl = $this->xpath->query("./ows:Metadata", $layerEl);
                $metadata = array();
                foreach($metadataUrlsEl as $metadataUrlEl) {
                    $metadata[] = $this->getValue("./xlink:href", $metadataUrlEl);
                }
                $layer->setMetadataURL($metadata);

                $stylesEl = $this->xpath->query("./wmts:Style", $layerEl);
                foreach($stylesEl as $styleEl) {
                    $layer->addStyle(
                            array(
                                "name"=>$this->getValue("./ows:Identifier/text()", $styleEl),
                                "title"=>$this->getValue("./ows:Title/text()", $styleEl),
                                "legendUrl"=> array (
                                "link" =>"")));
                }
                
                
                $format = array();
                $fromatsEl = $this->xpath->query("./wmts:Format", $layerEl);
                foreach($fromatsEl as $fromatEl) {
                    $format[] = $this->getValue("./text()", $fromatEl);
                }
                $layer->setRequestDataFormats($format);
                unset($format);
                //TODO InfoFormat
                $format = array();
                $fromatsEl = $this->xpath->query("./wmts:InfoFormat", $layerEl);
                foreach($fromatsEl as $fromatEl) {
                   $format[] = $this->getValue("./text()", $fromatEl);
                }
                $layer->setRequestInfoFormats($format);
                
                $tileMatrixSetLinks = array();
                $tileMatrixSetLinksEl = $this->xpath->query("./wmts:TileMatrixSetLink", $layerEl);
                foreach($tileMatrixSetLinksEl as $tileMatrixSetLinkEl) {
                   //TODO set formats
                    $tileMatrixSetLinks[] = $this->getValue("./wmts:TileMatrixSet/text()", $tileMatrixSetLinkEl);
                }
                $layer->setTileMatrixSetLink($tileMatrixSetLinks);
                $resourceURL = array();
                $resourceURLsEl = $this->xpath->query("./wmts:ResourceURL", $layerEl);
                foreach($resourceURLsEl as $resourceURLEl) {
                    $resourceURL[] = array(
                        "format" => $this->getValue("./@format", $resourceURLEl),
                        "resourceType" => $this->getValue("./@resourceType", $resourceURLEl),
                        "template" => $this->getValue("./@template", $resourceURLEl));
                }
                $layer->setResourceURL($resourceURL);
                $wmts->getLayer()->add($layer);
            }
            unset($layerlist);
            $tilematrixsetsEl = $this->xpath->query("./wmts:TileMatrixSet", $contents);
            if($tilematrixsetsEl!=null) {
                foreach($tilematrixsetsEl as $tilematrixsetEl) {
                    $tilematrixset = new TileMatrixSet();
                    $tilematrixset->setIdentifier($this->getValue("./ows:Identifier/text()", $tilematrixsetEl));
                    $tilematrixset->setTitle($this->getValue("./ows:Title/text()", $tilematrixsetEl));
                    $tilematrixset->setAbstract($this->getValue("./ows:Abstract/text()", $tilematrixsetEl));
//                    $tilematrixset->setKeyword($this->getValue("./ows:Keyword/text()", $tilematrixsetEl));// ????
                    $tilematrixset->setSupportedSRS($this->getValue("./ows:SupportedCRS/text()", $tilematrixsetEl));
                    $tilematrixset->setWellknowscaleset($this->getValue("./wmts:WellKnownScaleSet/text()", $tilematrixsetEl));
//                    $tilematrixset->setBoundingBox($this->getValue("./ows:BoundingBox/text()", $tilematrixsetEl));// ????
                    
                    $tilematrixesEl = $this->xpath->query("./wmts:TileMatrix", $tilematrixsetEl);
                    if($tilematrixesEl!=null) {
                        foreach($tilematrixesEl as $tilematrixEl) {
                            $tilematrix = new TileMatrix();
                            $tilematrix->setIdentifier($this->getValue("./ows:Identifier/text()", $tilematrixEl));
                            $tilematrix->setScaledenominator($this->getValue("./wmts:ScaleDenominator/text()", $tilematrixEl));
                            $tilematrix->setTopleftcorner($this->getValue("./wmts:TopLeftCorner/text()", $tilematrixEl));
                            $tilematrix->setTilewidth($this->getValue("./wmts:TileWidth/text()", $tilematrixEl));
                            $tilematrix->setTileheight($this->getValue("./wmts:TileHeight/text()", $tilematrixEl));
                            $tilematrix->setMatrixwidth($this->getValue("./wmts:MatrixWidth/text()", $tilematrixEl));
                            $tilematrix->setMatrixheight($this->getValue("./wmts:MatrixHeight/text()", $tilematrixEl));
                            $tilematrixset->addTilematrix($tilematrix->getAsArray());
                        }
                    }
                    $wmts->addTtilematrixset($tilematrixset->getAsArray());
                }
            }
        }
        unset($contents);
        // read Themes 
//        $theme = array();
        $themes = $this->xpath->query("./wmts:Themes/wmts:Theme", $root);
        if($themes != null){
            foreach($themes as $themeEl) {
                $theme =  $this->findTheme(null, $themeEl);
                $wfst->addTheme($theme);
            }
        }
/*        
<Themes>
    <Theme>
        <ows:Title>Foundation</ows:Title>
        <ows:Abstract>World reference data</ows:Abstract>
        <ows:Identifier>Foundation</ows:Identifier>
        <Theme>
            <ows:Title>Digital Elevation Model</ows:Title>
            <ows:Identifier>DEM</ows:Identifier>
            <LayerRef>etopo2</LayerRef>
        </Theme>
        <Theme>
            <ows:Title>Administrative Boundaries</ows:Title>
            <ows:Identifier>AdmBoundaries</ows:Identifier>
            <LayerRef>AdminBoundaries</LayerRef>
        </Theme>
    </Theme>
</Themes>
*/
        return $wmts;
    }
    
    private function findTheme($theme = null, $themeParentEl){
        $theme = $theme==null? newTheme():$theme;
//        $themesEl = $this->xpath->query("./wmts:Theme", $themeParentEl);
//        if($themesEl != null) {
//            foreach($themesEl as $themeEl) {
//                $theme = newTheme();
                $theme->setIdentifier($this->getValue("./ows:Identifier/text()", $themeParentEl));
                $theme->setTitle($this->getValue("./ows:Title/text()", $themeParentEl));
                $theme->setAbstract($this->getValue("./ows:Abstract/text()", $themeParentEl));
                $subthemesEl = $this->xpath->query("./wmts:Theme", $themeParentEl);
                if($subthemesEl != null) {
                    foreach($subthemesEl as $subthemeEl) {
                        $theme->addTheme(new Theme(), $subthemeEl);
                    }
                }
//            }
//        }
        return $theme;
    }
}
