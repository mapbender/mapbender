<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\WmsBundle\Component\Exception\ParsingException;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
* Class that Parses WMS 1.0.0 GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
* Parses WMS GetCapabilities documents
*/
class Wms100CapabilitiesParser extends WmsCapabilitiesParser {

    public function parse(){
        $wms  = new WmsSource();

        $wms->setName($this->getNodeValue("/WMT_MS_Capabilities/Service/Name"));
        $wms->setTitle($this->getNodeValue("/WMT_MS_Capabilities/Service/Title"));
        $wms->setDescription($this->getNodeValue("/WMT_MS_Capabilities/Service/Abstract"));
        $wms->setOnlineResource($this->getNodeValue("/WMT_MS_Capabilities/Service/OnlineResource"));
        $wms->setFees($this->getNodeValue("/WMT_MS_Capabilities/Service/Fees"));
        $wms->setAccessConstraints($this->getNodeValue("/WMT_MS_Capabilities/Service/AccessConstraints"));
            
        return $wms;
    }
    
}
