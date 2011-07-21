<?php

namespace MB\WMSBundle\Components;
use MB\WMSBundle\Entity\WMSService;
use MB\WMSBundle\Entity\WMSLayer;
use MB\WMSBundle\Entity\Layer;
use MB\WMSBundle\Entity\GroupLayer;

/**
* Class that Parses WMS GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
* Parses WMS GetCapabilities documents
*/
class CapabilitiesParser {

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
        $this->doc->loadXML($data);

        if(!@$this->doc->validate()){
            // TODO logging
        };
    }

    /**
    *   @return WMSService
    */
    public function getWMSService(){
        $wms = new WMSService();

        $wms->setVersion((string)$this->doc->documentElement->getAttribute("version"));
        foreach( $this->doc->documentElement->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){
                switch ($node->nodeName) {

                    case "Service":
                        foreach ($node->childNodes as $node){
                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                switch ($node->nodeName) {
                                    case "Name":
                                        $wms->setName($node->nodeValue);
                                    break;
                                    case "Title":
                                        $wms->setTitle($node->nodeValue);
                                    break;
                                    case "Abstract":
                                        $wms->setAbstract($node->nodeValue);
                                    break;
                                    case "KeywordList":
                                    break;
                                    case "OnlineResource":
                                    break;
                                    case "ContactInformation":
                                    break;
                                    case "Fees":
                                    break;
                                    case "AccessConstraints":
                                    break;
                                }
                            } 
                        }
                    break;
                    case "Capability":
                        foreach ($node->childNodes as $node){
                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                switch($node->nodeName){
                                    case "Request":
                                        foreach ($node->childNodes as $node){
                                            if($node->nodeType == XML_ELEMENT_NODE){ 
                                                 $this->requestDescriptionFromNode($wms,$node);
                                            }
                                        }
                                    break;
                                    case "Exception":
                                    break;
                                    case "VendorSpecificCapabilities":
                                    case "UserDefinedSymbolization":
                                    break;
                                    case "Layer":
                                        $sublayer = $this->WMSLayerFromLayerNode($node);
                                        $wms->getLayer()->add($sublayer);
                                    break;
                                }
                            }
                        }
                    break;
                }
            }
        }

        // check for mandatory elements
        if($wms->getName() === null){
            throw new \Exception("Mandatory Element Name not defined on Service");
        }
        return $wms;
    }

    /**
     * @param DOMNode a WMS layernode "<Layer>" to be converted to a Layer Objject
     * @return WMSLayer 
     */
    protected function WMSLayerFromLayerNode(\DOMNode $layerNode){

        $layer = new WMSLayer();
        $srs = array();

        foreach($layerNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){  
                switch ($node->nodeName) {
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
                    break;

                    case "LatLonBoundingBox":   
                        $bounds = array(4);
                        $bounds[0] = trim($node->getAttribute("minx"));
                        $bounds[1] = trim($node->getAttribute("miny"));
                        $bounds[2] = trim($node->getAttribute("maxx"));
                        $bounds[3] = trim($node->getAttribute("maxy"));
                        $layer->setLatLonBounds(implode(' ',$bounds));
                    break;

                    case "BoundingBox":
                    break;

                    case "KeywordList":
                        # $layer->addKeyword();
                    break;

                    case "Style":
                        # $layer->setStyle();
                    break;

                    case "ScaleHint":
                    break;

                    case "Layer":
                        $sublayer = $this->WMSLayerFromLayerNode($node);
                        $layer->getLayer()->add($sublayer);
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

    public function RequestDescriptionFromNode($wms,\DomNode $RequestNode){
        $formats = array();
        $get  = "";
        $post ="";
        foreach ($RequestNode->childNodes as $node){
            if($node->nodeType == XML_ELEMENT_NODE){ 
                switch ($node->nodeName) {
                    case "Format":
                        $formats[] = $node->nodeValue;
                    break;
                    case "DCPType":
                        try{
                            foreach ($node->childNodes as $httpnode){
                                if($httpnode->nodeType == XML_ELEMENT_NODE){ 
                                    foreach ($httpnode->childNodes as $methodnode){
                                        if($methodnode->nodeType == XML_ELEMENT_NODE){ 
                                            switch ($methodnode->nodeName) {
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

        switch($RequestNode->nodeName){
            case "GetMap":
                $wms->setGetMapGet($get);
                // FIXME: what if a format contains a , ?
                $wms->setGetMapFormats(implode(',',$formats));
            break;
            case "getCapabilities":
            case "getFeatureInfo":
            case "DescribeLayer":
            case "GetLegendGraphic":
            break;
        }

    }
    
}
