<?php

namespace MB\WMSBundle\Components;
use MB\WMSBundle\Entity\WMSService;

/**
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
            if($node->nodeType != XML_ELEMENT_NODE){ continue; };
            switch ($node->nodeName) {

                case "Service":
                    foreach ($node->childNodes as $node){
                        if($node->nodeType != XML_ELEMENT_NODE){ continue; };
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
                break;
                case "Capability":
                    foreach ($node->childNodes as $node){
                        switch($node->nodeName){
                            case "Request":
                            break;
                            case "Exception":
                            break;
                            case "VendorSpecificCapabilities":
                            case "UserDefinedSymbolization":
                            break;
                            case "Layer":
                                $wms = $this->parseLayers($node,$wms);
                            break;
                        }
                    }
                break;
            }
        }
        return $wms;
    }

   /**
    * @param DOMNode
    * @param WMSService
    * @return Array Array of Layers
    */
    protected function parseLayers(\DOMNode $node, WMSService $wms){

       return $wms; 
    }

   /**
    * @param DOMDocument
    * @param WMSService
    * @return WMSService
    */
    protected function parseMetadata(DOMDocument $doc, WMSService $wms){

    }

    
}
