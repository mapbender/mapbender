<?php
/*
* @package mapbender_wms_testing
* @author Karim Malhas <karim@malhas.de>
*/

use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Component\Exception\ParsingException;
use Mapbender\WmsBundle\Entity\WmsSource;

class Wms100CapabilitiesParserTest extends PHPUnit_Framework_TestCase {


    public function testParseServiceSection(){

        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMT_MS_Capabilities version="1.0.0">';
        $xml .= '<Service>';
        $xml .= '<Name>thename</Name>';
        $xml .= '<Title>thetitle</Title>';
        $xml .= '<Abstract>theabstract</Abstract>';
        $xml .= '<OnlineResource>theonlineResource</OnlineResource>';
        $xml .= '<Fees>thefees</Fees>';
        $xml .= '<AccessConstraints>theaccessconstraints</AccessConstraints>';
        $xml .= '</Service>';
        $xml .= '</WMT_MS_Capabilities>';

        $parser = WmsCapabilitiesParser::create($xml);

        $wms = $parser->parse();
        $this->assertEquals("thename",$wms->getName());
        $this->assertEquals("thetitle",$wms->getTitle());
        $this->assertEquals("theabstract",$wms->getDescription());
        $this->assertEquals("theonlineResource",$wms->getOnlineResource());
        $this->assertEquals("thefees",$wms->getfees());
        $this->assertEquals("theaccessconstraints",$wms->getAccessConstraints());
        $this->assertNull($wms->getContact());
    
    }

}
