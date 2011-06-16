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
                        # $layer->addSRS();
                    break;

                    case "LatLonBoundingBox":   
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

        // check for manadory elements
        if($layer->getTitle() === null){
            throw new \Exception("Invalid Layer definition, mandatory Field 'Title' not defined");
        }
        return $layer;
    }
    
}
