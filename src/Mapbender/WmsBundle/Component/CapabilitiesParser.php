<?php
//
//namespace Mapbender\WmsBundle\Component;
//use Mapbender\WmsBundle\Entity\WMSService;
//use Mapbender\WmsBundle\Entity\WMSLayer;
//use Mapbender\WmsBundle\Entity\Layer;
//use Mapbender\WmsBundle\Entity\GroupLayer;
//use Mapbender\WmsBundle\Component\Exception\ParsingException;
//
///**
//* Class that Parses WMS GetCapabilies Document 
//* @package Mapbender
//* @author Karim Malhas <karim@malhas.de>
//* Parses WMS GetCapabilities documents
//*/
//class CapabilitiesParser {
//
//    /**
//     * The XML representation of the Capabilites Document
//     * @var DOMDocument
//     */
//    protected $doc;
//    
//    /**
//    * @param DOMDocument the document to be parsed
//    */
//    public function __construct($data){
//
//        $this->doc = new \DOMDocument();
//        if(!$this->doc->loadXML($data)){
//            if(!$this->doc->loadHTML($data)){
//                  throw new \UnexpectedValueException("Could not parse CapabilitiesDocument.");
//            }
//        }
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
//
//        if(!@$this->doc->validate()){
//            // TODO logging
//        };
//    }
//
//    /**
//    *   @return WMSService
//    */
//    public function getWMSService(){
//        $wms = new WMSService();
//
//        $wms->setVersion((string)$this->doc->documentElement->getAttribute("version"));
//        foreach( $this->doc->documentElement->childNodes as $node){
//            if($node->nodeType == XML_ELEMENT_NODE){
//                switch ($node->localName) {
//
//                    case "Service":
//                        foreach ($node->childNodes as $node){
//                            if($node->nodeType == XML_ELEMENT_NODE){ 
//                                switch ($node->localName) {
//                                    case "Name":
//                                        $wms->setName($node->nodeValue);
//                                    break;
//                                    case "Title":
//                                        $wms->setTitle($node->nodeValue);
//                                    break;
//                                    case "Abstract":
//                                        $wms->setAbstract($node->nodeValue);
//                                    break;
//                                    case "KeywordList":
//                                    break;
//                                    case "OnlineResource":
//                                        $onlineResource = $node->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
//                                        $wms->setOnlineResource($onlineResource);
//                                    break;
//                                    case "ContactInformation":
//                                        $wms = $this->ContactInformationFromNode($wms,$node);
//                                    break;
//                                    case "Fees":
//                                        $wms->setFees($node->nodeValue);
//                                    break;
//                                    case "AccessConstraints":
//                                        $wms->setAccessConstraints($node->nodeValue);
//                                    break;
//                                }
//                            } 
//                        }
//                    break;
//                    case "Capability":
//                        foreach ($node->childNodes as $node){
//                            if($node->nodeType == XML_ELEMENT_NODE){ 
//                                switch($node->localName){
//                                    case "Request":
//                                        foreach ($node->childNodes as $node){
//                                            if($node->nodeType == XML_ELEMENT_NODE){ 
//                                                 $this->requestDescriptionFromNode($wms,$node);
//                                            }
//                                        }
//                                    break;
//                                    case "Exception":
//                                        foreach ($node->childNodes as $node){
//                                            if($node->nodeType == XML_ELEMENT_NODE){ 
//                                                if($node->localName == "Format"){
//                                                    $formats = $wms->getExceptionFormats();
//                                                    $formats[] = str_replace(".","__",$node->nodeValue);
//                                                    $wms->setExceptionFormats($formats);
//                                                }
//                                            }
//                                        }
//                                    break;
//                                    case "VendorSpecificCapabilities":
//                                    case "UserDefinedSymbolization":
//                                        // these can either be '0','1',nonexistant ( -> false) or another string
//                                        // we take 'any other string' to mean the same thing as '1'
//
//                                        $supportSLD = $node->getAttribute("SupportSLD");
//                                        if($supportSLD == '0' || $supportSLD === false){
//                                            $supportSLD = false;
//                                        } else { 
//                                            $supportSLD = true;
//                                        }
//                                        $wms->setSymbolSupportSLD($supportSLD);
//
//                                        $userLayer = $node->getAttribute("UserLayer"); 
//                                        if($userLayer == '0' || $userLayer === false){
//                                            $userLayer = false;
//                                        } else { 
//                                            $userLayer = true;
//                                        }
//                                        $wms->setSymbolUserLayer($userLayer);
//
//                                        $userStyle = $node->getAttribute("UserStyle");
//                                        if($userStyle == '0' || $userStyle === false){
//                                            $userStyle = false;
//                                        } else { 
//                                            $userStyle = true;
//                                        }
//                                        $wms->setSymbolUserStyle($userStyle);
//
//                                        $remoteWFS = $node->getAttribute("RemoteWFS");
//                                        if($remoteWFS == '0' || $remoteWFS === false){
//                                            $remoteWFS = false;
//                                        } else { 
//                                            $remoteWFS = true;
//                                        }
//                                        $wms->setSymbolRemoteWFS($remoteWFS);
//                                    break;
//                                    case "Layer":
//                                        foreach ($node->childNodes as $subnode){
//                                            switch ($subnode->localName) {
//                                                case "CRS":
//                                                    $wms->addSrs($subnode->nodeValue);
//                                                    break;
//                                                case "SRS":
//                                                    $wms->addSrs($subnode->nodeValue);
//                                                    break;
//                                                default:
//                                                    break;
//                                            }
//                                        }
//                                        $sublayer = $this->WMSLayerFromLayerNode($node);
//                                        $wms->getLayer()->add($sublayer);
//                                    break;
//                                }
//                            }
//                        }
//                    break;
//                }
//            }
//        }
//
//        // check for mandatory elements
//        if($wms->getName() === null){
//            throw new \Exception("Mandatory Element Name not defined on Service");
//        }
//        return $wms;
//    }
//
//    /**
//     *  @param Mapbender\WmsBundle\WMSService The WMS that needs the contact information
//     *  @param \DOMNode the <contactInformation> node of the WMS
//     *  @return the wms
//     */
//    protected function ContactInformationFromNode($wms,\DOMElement $contactNode){
//        foreach($contactNode->childNodes as $node){
//            if($node->nodeType == XML_ELEMENT_NODE){  
//                switch ($node->localName) {
//                    case "ContactPersonPrimary":
//                        foreach($node->childNodes as $subnode){
//                            if($subnode->nodeType == XML_ELEMENT_NODE){  
//                                switch ($subnode->localName) {
//                                    case "ContactPerson":
//                                        $wms->setContactPerson($subnode->nodeValue);
//                                    break;
//                                    case "ContactOrganization":
//                                        $wms->setContactOrganization($subnode->nodeValue);
//                                    break;
//                                }
//                            }
//                        }
//                    break;
//                    case "ContactPosition":
//                        $wms->setContactPosition($node->nodeValue);
//                    break;
//                    case "ContactAddress":
//                        foreach($node->childNodes as $subnode){
//                            if($subnode->nodeType == XML_ELEMENT_NODE){  
//                                switch ($subnode->localName) {
//                                    case "Address":
//                                        $wms->setContactAddress($subnode->nodeValue);
//                                    break;
//                                    case "AddressType":
//                                        $wms->setContactAddressType($subnode->nodeValue);
//                                    break;
//                                    case "City":
//                                        $wms->setContactAddressCity($subnode->nodeValue);
//                                    break;
//                                    case "StateOrProvince":
//                                        $wms->setContactAddressStateOrProvince($subnode->nodeValue);
//                                    break;
//                                    case "PostCode":
//                                        $wms->setContactAddressPostCode($subnode->nodeValue);
//                                    break;
//                                    case "Country":
//                                        $wms->setContactAddressCountry($subnode->nodeValue);
//                                    break;
//                                }
//                            }
//                        }
//                    break;
//                    case "ContactVoiceTelephone":
//                        $wms->setContactVoiceTelephone($node->nodeValue);
//                    break;
//                    case "ContactFacsimileTelephone":
//                        $wms->setContactFacsimileTelephone($node->nodeValue);
//                    break;
//                    case "ContactElectronicMailAddress":
//                        $wms->setContactElectronicMailAddress($node->nodeValue);
//                    break;
//                }
//            }
//        }
//        return $wms;
//    }
//
//    /**
//     * @param DOMNode a WMS layernode "<Layer>" to be converted to a Layer Objject
//     * @return WMSLayer 
//     */
//    protected function WMSLayerFromLayerNode(\DOMNode $layerNode){
//
//        $layer = new WMSLayer();
//        $srs = array();
////        queryable
//        $queryable = $layerNode->getAttribute("queryable");
//        if($queryable !== null && ($queryable == "0" || $queryable == "1")){
//            $layer->setQueryable($queryable);
//        }
//        foreach($layerNode->childNodes as $node){
//            if($node->nodeType == XML_ELEMENT_NODE){  
//                switch ($node->localName) {
//                    case "Name":
//                        $layer->setName($node->nodeValue);
//                    break;
//                    
//                    case "Title":
//                        $layer->setTitle($node->nodeValue);
//                    break;
//
//                    case "Abstract":
//                        $layer->setAbstract($node->nodeValue);
//                    break;
//                    
//                    case "SRS":
//                        $srs[] = $node->nodeValue;
//                        
//                    case "CRS":
//                        $srs[] = $node->nodeValue;
//                    break;
//
//                    case "LatLonBoundingBox":   
//                        $bounds = array(4);
//                        $bounds[0] = trim($node->getAttribute("minx"));
//                        $bounds[1] = trim($node->getAttribute("miny"));
//                        $bounds[2] = trim($node->getAttribute("maxx"));
//                        $bounds[3] = trim($node->getAttribute("maxy"));
//                        $layer->setLatLonBounds(implode(' ',$bounds));
//                    break;
//                
//                    case "EX_GeographicBoundingBox":
//                        $xmin = null; $xmax = null; $ymin = null; $ymax = null;
//                        foreach($node->childNodes as $bbnode){
//                            if($bbnode->nodeType == XML_ELEMENT_NODE){  
//                                switch ($bbnode->localName) {
//                                    case "westBoundLongitude":
//                                        $xmin = $bbnode->nodeValue;
//                                    break;
//                                    case "southBoundLatitude":
//                                        $miny = $bbnode->nodeValue;
//                                    break;
//                                    case "eastBoundLongitude":
//                                        $maxx = $bbnode->nodeValue;
//                                    break;
//                                    case "northBoundLatitude":
//                                        $maxy = $bbnode->nodeValue;
//                                    break;
//                                }
//                            }
//                        }
//                        $bounds = array(4);
//                        $bounds[0] = $xmin;
//                        $bounds[1] = $miny;
//                        $bounds[2] = $maxx;
//                        $bounds[3] = $maxy;
//                        $layer->setLatLonBounds(implode(' ',$bounds));
//                    break;
//
//                    case "BoundingBox": //v 1.3.0
//                    break;
//
//                    case "KeywordList":
//                        # $layer->addKeyword();
//                    break;
//
//                    case "Style":
//                        $style = array(
//                            "name"=>"",
//                            "title"=>"",
//                            "legendUrl"=> array (
//                                "link" =>""
//                            ),
//                        );
//                        foreach($node->childNodes as $styleChild){
//                            if($styleChild->nodeType == XML_ELEMENT_NODE){  
//                                switch ($styleChild->localName) {
//                                    case "Name":
//                                        $style["name"]= $styleChild->nodeValue;
//                                    break;
//                                    case "Title":
//                                        $style["title"]= $styleChild->nodeValue;
//                                    break;
//                                    case "LegendURL":
//                                        foreach($styleChild->childNodes as $legendChild){
////                                            $t = $legendChild->localName;
//                                            if($legendChild->nodeType == XML_ELEMENT_NODE){  
//                                                switch ($legendChild->localName) {
//                                                    case "Format":
//                                                    break;
//                                                    case "OnlineResource":
//                                                        $style["legendUrl"]["link"] = $legendChild->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
//                                                    break;
//                                                }
//                                            }
//                                        }
//                                    break;
//                                }
//                            }
//                        }
//                        $layer->addStyle($style);
//                    break;
//
//                    case "ScaleHint":
//                        $layer->setScaleHintMin(trim($node->getAttribute("min")));
//                        $layer->setScaleHintMax(trim($node->getAttribute("max")));
//                    break;
//                    
//                        foreach($node->childNodes as $metadataChild){
//                            if($metadataChild->nodeType == XML_ELEMENT_NODE){  
//                                switch ($metadataChild->localName) {
//                                    case "Format":
//                                    break;
//                                    case "OnlineResource":
//                                        $layer->setMetadataURL($metadataChild->getAttributeNS("http://www.w3.org/1999/xlink" ,"href"));
//                                    break;
//                                }
//                            }
//                        }
//                    break;
//                    
//                    case "DataURL":
//                        foreach($node->childNodes as $dataChild){
//                            if($dataChild->nodeType == XML_ELEMENT_NODE){  
//                                switch ($dataChild->localName) {
//                                    case "Format":
//                                    break;
//                                    case "OnlineResource":
//                                        $layer->setDataURL($dataChild->getAttributeNS("http://www.w3.org/1999/xlink" ,"href"));
//                                    break;
//                                }
//                            }
//                        }
//                    break;
//
//                    case "Layer":
//                        $sublayer = $this->WMSLayerFromLayerNode($node);
//                        if($sublayer->getLatLonBounds() === null && $layer->getLatLonBounds() !== null){
//                            $sublayer->setLatLonBounds($layer->getLatLonBounds());
//                        }
//                        $layer->getLayer()->add($sublayer);
//                    break;
//                    
//                }
//            }
//        }
//
//        $srs = implode(',',$srs);
//        $layer->setSRS($srs);
//        // check for manadory elements
//        if($layer->getTitle() === null){
//            throw new \Exception("Invalid Layer definition, mandatory Field 'Title' not defined");
//        }
//        return $layer;
//    }
//
//    /**
//     *  @param Mapbender\WmsBundle\WMSService The WMS that needs the request information
//     *  @param \DOMNode a childElement of the <Request> element
//     *  @return the wms
//     */
//    public function requestDescriptionFromNode($wms,\DomElement $RequestNode){
//        $formats = array();
//        $get  = "";
//        $post ="";
//        foreach ($RequestNode->childNodes as $node){
//            if($node->nodeType == XML_ELEMENT_NODE){ 
//                switch ($node->localName) {
//                    case "Format":
//                        // WORKAROUND: Symfony uses '.' as a PropertyPath seperator which creates problems If we want to use the nodeValue 
//                        // as a list of checkboxes to show what the wms supports
//                        $formats[] = str_replace(".","__",$node->nodeValue);
//                    break;
//                    case "DCPType":
//                        try{
//                            foreach ($node->childNodes as $httpnode){
//                                if($httpnode->nodeType == XML_ELEMENT_NODE){ 
//                                    foreach ($httpnode->childNodes as $methodnode){
//                                        if($methodnode->nodeType == XML_ELEMENT_NODE){ 
//                                            switch ($methodnode->localName) {
//                                                case "Get":
//                                                    foreach ($methodnode->childNodes as $resnode){
//                                                        if($resnode->nodeType == XML_ELEMENT_NODE){ 
//                                                            $get = $resnode->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
//                                                        }
//                                                    }
//                                                break;
//                                                case "Post":
//                                                    foreach ($methodnode->childNodes as $resnode){
//                                                        if($resnode->nodeType == XML_ELEMENT_NODE){ 
//                                                            $post = $resnode->getAttributeNS("http://www.w3.org/1999/xlink" ,"href");
//                                                        }
//                                                    }
//                                                break;
//                                            }
//                                        }
//                                    }
//                                }
//                            }
//                        }catch(\Exception $E){
//                            throw $E;
//                        }
//                    break;
//                }
//            }
//        }
//        if($RequestNode->hasAttribute("name")){
//            $operation_name = $RequestNode->getAttribute("name");
//        } else {
//            $operation_name = $RequestNode->localName;
//        }
//        switch($operation_name){
//            case "GetCapabilities":
//                $wms->setRequestGetCapabilitiesGET($get);
//                $wms->setRequestGetCapabilitiesPOST($post);
//                $wms->setRequestGetCapabilitiesFormats($formats);
//            break;
//            case "GetMap":
//                $wms->setRequestGetMapGET($get);
//                $wms->setRequestGetMapPOST($post);
//                $wms->setRequestGetMapFormats($formats);
//            break;
//            case "GetFeatureInfo":
//                $wms->setRequestGetFeatureInfoGET($get);
//                $wms->setRequestGetFeatureInfoPOST($post);
//                $wms->setRequestGetFeatureInfoFormats($formats);
//            break;
//            case "DescribeLayer":
//                $wms->setRequestDescribeLayerGET($get);
//                $wms->setRequestDescribeLayerPOST($post);
//                $wms->setRequestDescribeLayerFormats($formats);
//            break;
//            case "GetLegendGraphic":
//                $wms->setRequestGetLegendGraphicGET($get);
//                $wms->setRequestGetLegendGraphicPOST($post);
//                $wms->setRequestGetLegendGraphicFormats($formats);
//            break;
//            case "GetStyles":
//                $wms->setRequestGetStylesGET($get);
//                $wms->setRequestGetStylesPOST($post);
//                $wms->setRequestGetStylesFormats($formats);
//            break;
//            case "PutStyles":
//                $wms->setRequestPutStylesGET($get);
//                $wms->setRequestPutStylesPOST($post);
//                $wms->setRequestPutStylesFormats($formats);
//            break;
//        }
//
//    }
//}
