<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Entity\Contact;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Entity\RequestInformation;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
//use Mapbender\WmsBundle\Entity\Layer;
//use Mapbender\WmsBundle\Entity\GroupLayer;
use Mapbender\WmsBundle\Component\Exception\ParsingException;

/**
* Class that Parses WMS GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
* Parses WMS GetCapabilities documents
*/
class WmsCapabilitiesParser {

    /**
     * The XML representation of the Capabilites Document
     * @var DOMDocument
     */
    protected $doc;
    
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
        if($this->doc->documentElement->tagName == "ServiceExceptionReport"){
            $message=$this->doc->documentElement->nodeValue;
            throw new  ParsingException($message);
        
        }

        $version = $this->doc->documentElement->getAttribute("version");
        switch($version){

            case "1.0.0":
            case "1.1.0":
            case "1.1.1":
            case "1.3.0":
            default:
            break;

        }

        if(!@$this->doc->validate()){
            // TODO logging
        };
    }

    /**
    *   @return WmsSource
    */
    public function getWmsSource(){
        $wmssource = new WmsSource();

        $wmssource->setVersion((string)$this->doc->documentElement->getAttribute("version"));
        foreach( $this->doc->documentElement->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){
                switch ($node->localName) {

                    case "Service":
                        foreach ($node->childNodes as $node){
                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                switch ($node->localName) {
                                    case "Name":
                                        $wmssource->setName($node->nodeValue);
                                    break;
                                    case "Title":
                                        $wmssource->setTitle($node->nodeValue);
                                    break;
                                    case "Abstract":
                                        $wmssource->setDescription($node->nodeValue);
                                    break;
                                    case "KeywordList":
                                        //@TODO
                                    break;
                                    case "OnlineResource":
                                        $onlineResource = $node->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
                                        $wmssource->setOnlineResource($onlineResource);
                                    break;
                                    case "ContactInformation":
                                        $contact = $this->getContactInformationFromNode($node);
                                        $wmssource->setContact($contact);
                                    break;
                                    case "Fees":
                                        $wmssource->setFees($node->nodeValue);
                                    break;
                                    case "AccessConstraints":
                                        $wmssource->setAccessConstraints($node->nodeValue);
                                    break;
                                }
                            } 
                        }
                    break;
                    case "Capability":
                        foreach ($node->childNodes as $node){
                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                switch($node->localName){
                                    case "Request":
                                        foreach ($node->childNodes as $node){
                                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                                 $this->setRequestDescriptionFromNode($wmssource, $node);
                                            }
                                        }
                                    break;
                                    case "Exception":
                                        foreach ($node->childNodes as $node){
                                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                                if($node->localName == "Format"){
                                                    $formats = $wmssource->getExceptionFormats();
                                                    $formats[] = str_replace(".","__",$node->nodeValue);
                                                    $wmssource->setExceptionFormats($formats);
                                                }
                                            }
                                        }
                                    break;
                                    case "VendorSpecificCapabilities":
                                    case "UserDefinedSymbolization":
                                        // these can either be '0','1',nonexistant ( -> false) or another string
                                        // we take 'any other string' to mean the same thing as '1'

                                        $supportSLD = $node->getAttribute("SupportSLD");
                                        if($supportSLD == '0' || $supportSLD === false){
                                            $supportSLD = false;
                                        } else { 
                                            $supportSLD = true;
                                        }
                                        $wmssource->setSupportsSld($supportSLD);

                                        $userLayer = $node->getAttribute("UserLayer"); 
                                        if($userLayer == '0' || $userLayer === false){
                                            $userLayer = false;
                                        } else { 
                                            $userLayer = true;
                                        }
                                        $wmssource->setUserLayer($userLayer);

                                        $userStyle = $node->getAttribute("UserStyle");
                                        if($userStyle == '0' || $userStyle === false){
                                            $userStyle = false;
                                        } else { 
                                            $userStyle = true;
                                        }
                                        $wmssource->setUserStyle($userStyle);

                                        $remoteWFS = $node->getAttribute("RemoteWFS");
                                        if($remoteWFS == '0' || $remoteWFS === false){
                                            $remoteWFS = false;
                                        } else { 
                                            $remoteWFS = true;
                                        }
                                        $wmssource->setRemoteWfs($remoteWFS);
                                    break;
                                    case "Layer":
                                        foreach ($node->childNodes as $subnode){
                                            switch ($subnode->localName) {
                                                case "CRS":
                                                    $wmssource->addSrs($subnode->nodeValue);
                                                    break;
                                                case "SRS":
                                                    $wmssource->addSrs($subnode->nodeValue);
                                                    break;
                                                default:
                                                    break;
                                            }
                                        }
                                        $sublayer = $this->getWmsLayerFromLayerNode($node);
                                        $wmssource->getLayer()->add($sublayer);
                                    break;
                                }
                            }
                        }
                    break;
                }
            }
        }

        // check for mandatory elements
        if($wmssource->getName() === null){
            throw new \Exception("Mandatory Element Name not defined on Service");
        }
        return $wmssource;
    }

    /**
     *  @param \DOMNode the <contactInformation> node of the WMS
     *  @return the Contact
     */
    protected function getContactInformationFromNode(\DOMElement $contactNode){
        $contact = new Contact();
        foreach($contactNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){  
                switch ($node->localName) {
                    case "ContactPersonPrimary":
                        foreach($node->childNodes as $subnode){
                            if($subnode->nodeType == XML_ELEMENT_NODE){  
                                switch ($subnode->localName) {
                                    case "ContactPerson":
                                        $contact->setPerson($subnode->nodeValue);
                                    break;
                                    case "ContactOrganization":
                                        $contact->setOrganization($subnode->nodeValue);
                                    break;
                                }
                            }
                        }
                    break;
                    case "ContactPosition":
                        $contact->setPosition($node->nodeValue);
                    break;
                    case "ContactAddress":
                        foreach($node->childNodes as $subnode){
                            if($subnode->nodeType == XML_ELEMENT_NODE){  
                                switch ($subnode->localName) {
                                    case "Address":
                                        $contact->setAddress($subnode->nodeValue);
                                    break;
                                    case "AddressType":
                                        $contact->setAddressType($subnode->nodeValue);
                                    break;
                                    case "City":
                                        $contact->setAddressCity($subnode->nodeValue);
                                    break;
                                    case "StateOrProvince":
                                        $contact->setAddressStateOrProvince($subnode->nodeValue);
                                    break;
                                    case "PostCode":
                                        $contact->setAddressPostCode($subnode->nodeValue);
                                    break;
                                    case "Country":
                                        $contact->setAddressCountry($subnode->nodeValue);
                                    break;
                                }
                            }
                        }
                    break;
                    case "ContactVoiceTelephone":
                        $contact->setVoiceTelephone($node->nodeValue);
                    break;
                    case "ContactFacsimileTelephone":
                        $contact->setFacsimileTelephone($node->nodeValue);
                    break;
                    case "ContactElectronicMailAddress":
                        $contact->setElectronicMailAddress($node->nodeValue);
                    break;
                }
            }
        }
        return $contact;
    }

    /**
     * @param DOMNode a WMS layernode "<Layer>" to be converted to a WmsLayerSource Object
     * @return WmsLayerSource 
     */
    protected function getWmsLayerFromLayerNode(\DOMNode $layerNode){

        $layer = new WmsLayerSource();
        $srs = array();
        $queryable = $layerNode->getAttribute("queryable");
        if($queryable !== null && ($queryable == "0" || $queryable == "1")){
            $layer->setQueryable($queryable);
        }
        foreach($layerNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){  
                switch ($node->localName) {
                    case "Name":
                        $layer->setName($node->nodeValue);
                    break;
                    
                    case "Title":
                        $layer->setTitle($node->nodeValue);
                    break;

                    case "Abstract":
                        $layer->setAbstract($node->nodeValue);
                    break;
                    
                    case "SRS":
                        $srs[] = $node->nodeValue;
                        
                    case "CRS":
                        $srs[] = $node->nodeValue;
                    break;

                    case "LatLonBoundingBox":   
                        $bbox = new BoundingBox();
                        // TODO CRS/SRS Format
                        $bbox->setSrs("EPSG:4326");
                        $bbox->setMinx(floatval(trim($node->getAttribute("minx"))));
                        $bbox->setMiny(floatval(trim($node->getAttribute("miny"))));
                        $bbox->setMaxx(floatval(trim($node->getAttribute("maxx"))));
                        $bbox->setMaxy(floatval(trim($node->getAttribute("maxy"))));
                        $layer->setLatLonBounds($bbox);
                    break;
                
                    case "EX_GeographicBoundingBox":
                        $minx = null; $maxx = null; $miny = null; $maxy = null;
                        foreach($node->childNodes as $bbnode){
                            if($bbnode->nodeType == XML_ELEMENT_NODE){  
                                switch ($bbnode->localName) {
                                    case "westBoundLongitude":
                                        $minx = $bbnode->nodeValue;
                                    break;
                                    case "southBoundLatitude":
                                        $miny = $bbnode->nodeValue;
                                    break;
                                    case "eastBoundLongitude":
                                        $maxx = $bbnode->nodeValue;
                                    break;
                                    case "northBoundLatitude":
                                        $maxy = $bbnode->nodeValue;
                                    break;
                                }
                            }
                        }
                        $bbox = new BoundingBox();
                        // TODO CRS/SRS Format
                        $bbox->setSrs("EPSG:4326");
                        $bbox->setMinx(floatval(trim($minx)));
                        $bbox->setMiny(floatval(trim($miny)));
                        $bbox->setMaxx(floatval(trim($maxx)));
                        $bbox->setMaxy(floatval(trim($maxy)));
                        $layer->setLatLonBounds($bbox);
                    break;

                    case "BoundingBox": //v 1.3.0
                        $bbox = new BoundingBox();
                        // TODO CRS/SRS Format
                        $srs = $node->getAttribute("SRS") ? $node->getAttribute("SRS") : $node->getAttribute("CRS");
                        $bbox->setSrs($srs);
                        $bbox->setMinx(floatval(trim($node->getAttribute("minx"))));
                        $bbox->setMiny(floatval(trim($node->getAttribute("miny"))));
                        $bbox->setMaxx(floatval(trim($node->getAttribute("maxx"))));
                        $bbox->setMaxy(floatval(trim($node->getAttribute("maxy"))));
                        $layer->addBoundingBox($bbox);
                    break;

                    case "KeywordList":
                        // TODO
                    break;

                    case "Style":
                        $style = new Style();
                        foreach($node->childNodes as $styleChild){
                            if($styleChild->nodeType == XML_ELEMENT_NODE){  
                                switch ($styleChild->localName) {
                                    case "Name":
//                                        $style["name"]= $styleChild->nodeValue;
                                        $style->setName($styleChild->nodeValue);
                                    break;
                                    case "Title":
//                                        $style["title"]= $styleChild->nodeValue;
                                        $style->setTitle($styleChild->nodeValue);
                                    break;
                                    case "Abstract":
//                                        $style["title"]= $styleChild->nodeValue;
                                        $style->setAbstract($styleChild->nodeValue);
                                    break;
                                    case "LegendURL":
                                        $legendUrl = new LegendUrl();
                                        $legendUrl->setWidth(intval($styleChild->getAttribute("width")));
                                        $legendUrl->setHeight(intval($styleChild->getAttribute("height")));
                                        $onlineResource = new OnlineResource();
                                        foreach($styleChild->childNodes as $legendChild){
//                                            $t = $legendChild->localName;
                                            if($legendChild->nodeType == XML_ELEMENT_NODE){  
                                                switch ($legendChild->localName) {
                                                    case "Format":
                                                        $onlineResource->setFormat($legendChild->nodeValue);
                                                    break;
                                                    case "OnlineResource":
                                                        $onlineResource->setHref($legendChild->getAttributeNS("http://www.w3.org/1999/xlink" ,"href"));
                                                    break;
                                                }
                                            }
                                        }
                                        $style->setLegendUrl($legendUrl);
                                    break;
                                }
                            }
                        }
                        $layer->addStyle($style);
                    break;

                    case "ScaleHint":
                        $layer->setMinScale(floatval(trim($node->getAttribute("min"))));
                        $layer->setMaxScale(floatval(trim($node->getAttribute("max"))));
                    break;
                    case "MetadataURL":
                        $metadataUrl = new MetadataUrl();
                        $metadataUrl->setType($node->getAttribute("type"));
                        $onlineResource = new OnlineResource();
                        foreach($node->childNodes as $metadataChild){
                            if($metadataChild->nodeType == XML_ELEMENT_NODE){
                                switch ($metadataChild->localName) {
                                    case "Format":
                                        $onlineResource->setFormat($metadataChild->nodeValue);
                                    break;
                                    case "OnlineResource":
                                        $onlineResource->setHref($metadataChild->getAttributeNS("http://www.w3.org/1999/xlink" ,"href"));
                                    break;
                                }
                            }
                        }
                    break;
                    
                    case "DataURL":
                        foreach($node->childNodes as $dataChild){
                            if($dataChild->nodeType == XML_ELEMENT_NODE){  
                                switch ($dataChild->localName) {
                                    case "Format":
                                    break;
                                    case "OnlineResource":
                                        $layer->setDataURL($dataChild->getAttributeNS("http://www.w3.org/1999/xlink" ,"href"));
                                    break;
                                }
                            }
                        }
                    break;

                    case "Layer":
                        $sublayer = $this->getWmsLayerFromLayerNode($node);
//                        if($sublayer->getLatLonBounds() === null && $layer->getLatLonBounds() !== null){
//                            $sublayer->setLatLonBounds($layer->getLatLonBounds());
//                        }
                        $sublayer->setParent($layer);
                    break;
                    
                }
            }
        }

        $srs = implode(',',$srs);
        $layer->setSRS($srs);
        // check for manadory elements
        if($layer->getTitle() === null){
            throw new \Exception("Invalid Layer definition, mandatory Field 'Title' not defined");
        }
        return $layer;
    }

    /**
     *  @param Mapbender\WmsBundle\WMSService The WMS that needs the request information
     *  @param \DOMNode a childElement of the <Request> element
     */
    public function setRequestDescriptionFromNode(WmsSource $wmssource,\DomElement $RequestNode){
        $formats = array();
        $get  = "";
        $post ="";
        foreach ($RequestNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){ 
                switch ($node->localName) {
                    case "Format":
                        // WORKAROUND: Symfony uses '.' as a PropertyPath seperator which creates problems If we want to use the nodeValue 
                        // as a list of checkboxes to show what the wms supports
                        $formats[] = str_replace(".","__",$node->nodeValue);
                    break;
                    case "DCPType":
                        try{
                            foreach ($node->childNodes as $httpnode){
                                if($httpnode->nodeType == XML_ELEMENT_NODE){ 
                                    foreach ($httpnode->childNodes as $methodnode){
                                        if($methodnode->nodeType == XML_ELEMENT_NODE){ 
                                            switch ($methodnode->localName) {
                                                case "Get":
                                                    foreach ($methodnode->childNodes as $resnode){
                                                        if($resnode->nodeType == XML_ELEMENT_NODE){ 
                                                            $get = $resnode->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
                                                        }
                                                    }
                                                break;
                                                case "Post":
                                                    foreach ($methodnode->childNodes as $resnode){
                                                        if($resnode->nodeType == XML_ELEMENT_NODE){ 
                                                            $post = $resnode->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
                                                        }
                                                    }
                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        }catch(\Exception $E){
                            throw $E;
                        }
                    break;
                }
            }
        }
        if($RequestNode->hasAttribute("name")){
            $operation_name = $RequestNode->getAttribute("name");
        } else {
            $operation_name = $RequestNode->localName;
        }
        switch($operation_name){
            case "GetCapabilities":
                $requestinformation = new RequestInformation();
                $requestinformation->setHttpGet($get);
                $requestinformation->setHttpPost($post);
                $requestinformation->setFormats($formats);
                $wmssource->setGetCapabilities($requestinformation);
            break;
            case "GetMap":
                $requestinformation = new RequestInformation();
                $requestinformation->setHttpGet($get);
                $requestinformation->setHttpPost($post);
                $requestinformation->setFormats($formats);
                $wmssource->setGetMap($requestinformation);
            break;
            case "GetFeatureInfo":
                $requestinformation = new RequestInformation();
                $requestinformation->setHttpGet($get);
                $requestinformation->setHttpPost($post);
                $requestinformation->setFormats($formats);
                $wmssource->setGetFeatureInfo($requestinformation);
            break;
            case "DescribeLayer":
                $requestinformation = new RequestInformation();
                $requestinformation->setHttpGet($get);
                $requestinformation->setHttpPost($post);
                $requestinformation->setFormats($formats);
                $wmssource->setDescribeLayer($requestinformation);
            break;
            case "GetLegendGraphic":
                $requestinformation = new RequestInformation();
                $requestinformation->setHttpGet($get);
                $requestinformation->setHttpPost($post);
                $requestinformation->setFormats($formats);
                $wmssource->setGetLegendGraphic($requestinformation);
            break;
            case "GetStyles":
                $requestinformation = new RequestInformation();
                $requestinformation->setHttpGet($get);
                $requestinformation->setHttpPost($post);
                $requestinformation->setFormats($formats);
                $wmssource->setGetStyles($requestinformation);
            break;
            case "PutStyles":
                $requestinformation = new RequestInformation();
                $requestinformation->setHttpGet($get);
                $requestinformation->setHttpPost($post);
                $requestinformation->setFormats($formats);
                $wmssource->setPutStyles($requestinformation);
            break;
        }
    }
}
