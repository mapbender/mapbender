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

        $wms->setName($this->getValue("/WMT_MS_Capabilities/Service/Name/text()"));
        $wms->setTitle($this->getValue("/WMT_MS_Capabilities/Service/Title/text()"));
        $wms->setDescription($this->getValue("/WMT_MS_Capabilities/Service/Abstract/text()"));
        $wms->setOnlineResource($this->getValue("/WMT_MS_Capabilities/Service/OnlineResource/text()"));
        $wms->setFees($this->getValue("/WMT_MS_Capabilities/Service/Fees/text()"));
        $wms->setAccessConstraints($this->getValue("/WMT_MS_Capabilities/Service/AccessConstraints/text()"));
            
        return $wms;
    }
    
}
