<?php

namespace Mapbender\WmsBundle\Component;

use Mapbender\WmsBundle\Component\Exception\ParsingException;

/**
* Class that Parses WMS 1.1.1 GetCapabilies Document 
* @package Mapbender
* @author Karim Malhas <karim@malhas.de>
*/
class Wms111CapabilitiesParser extends WmsCapabilitiesParser {

    public function parse(){
        return new WmsSource();
    }
}
