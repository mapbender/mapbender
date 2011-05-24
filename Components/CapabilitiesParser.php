<?php

namespace MB\WMSBundle\Components;
use MB\WMSBundle\Entity\WMSService;

/**
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
* Parses WMS GetCapabilities documents
*/
class CapabilitiesParser {

    protected $doc;
    
    /**
    * @param DOMDocument the document to be parsed
    */
    public function __construct(\DOMDocument $doc){
        $this->doc = $doc;
    }

    /**
    *   @return WMSService
    */
    public function getWMSService(){
        $wms = new WMSService();
        // FIXME: NEVER USE THIS PARSER IN PRODUCTION

        foreach( $this->doc->documentElement->childNodes as $node){
            if($node->nodeType != XML_ELEMENT_NODE){ continue; };
            switch ($node->nodeName) {

                case "Service":
                    foreach ($node->childNodes as $node){
                        if($node->nodeType != XML_ELEMENT_NODE){ continue; };
                        switch ($node->nodeName) {
                            case "Title":
                                $wms->setTitle($node->nodeValue);
                            break;
                            case "Name":
                                $wms->setName($node->nodeValue);
                            break;
                            case "Abstract":
                                $wms->setAbstract($node->nodeValue);
                            break;
                        }
                     
                    }
            }
        }


        return  $wms;

    
    }
}
