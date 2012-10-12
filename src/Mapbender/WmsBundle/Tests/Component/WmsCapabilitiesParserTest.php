<?php
/*
* @package mapbender_wms_testing
* @author Karim Malhas <karim@malhas.de>
*/

use Mapbender\WmsBundle\Component\WmsCapabilitiesParser;
use Mapbender\WmsBundle\Component\Exception\ParsingException;

class WmsCapabilitiesParserTest extends PHPUnit_Framework_TestCase {


    public function testVersion100(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMT_MS_Capabilities version="1.0.0"></WMT_MS_Capabilities>';

        $parser = WmsCapabilitiesParser::create($xml);
        $this->assertInstanceOf("Mapbender\WmsBundle\Component\Wms100CapabilitiesParser",$parser);
    }

    public function testVersion110(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMT_MS_Capabilities version="1.1.0"></WMT_MS_Capabilities>';

        $parser = WmsCapabilitiesParser::create($xml);
        $this->assertInstanceOf("Mapbender\WmsBundle\Component\Wms110CapabilitiesParser",$parser);
    }
    
    public function testVersion111(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMT_MS_Capabilities version="1.1.1"></WMT_MS_Capabilities>';

        $parser = WmsCapabilitiesParser::create($xml);
        $this->assertInstanceOf("Mapbender\WmsBundle\Component\Wms111CapabilitiesParser",$parser);
    }
    
    public function testVersion130(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMS_Capabilities xmlns="http://www.opengis.net/wms" version="1.3.0"></WMS_Capabilities>';

        $parser = WmsCapabilitiesParser::create($xml);
        $this->assertInstanceOf("Mapbender\WmsBundle\Component\Wms130CapabilitiesParser",$parser);
    }

    /**
     * @expectedException Mapbender\WmsBundle\Component\Exception\ParsingException
     */
    public function testVersionInvalid(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMS_Capabilities version="999"></WMS_Capabilities>';

        $parser = WmsCapabilitiesParser::create($xml);
    }
    
    /**
     * @expectedException Mapbender\WmsBundle\Component\Exception\ParsingException
     */
    public function testGarbledData(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMS_Capabilities version="999"></WMS_Capabi';

        $parser = WmsCapabilitiesParser::create($xml);
    }
    
    public function testOldStyleException(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<WMTException>A</WMTException>';

        try{
            $parser = WmsCapabilitiesParser::create($xml);
        }catch (ParsingException $E){
            $this->assertEquals("A",$E->getMessage()); 
            return;
        }
        $this->fail();
    }
    
    public function testNewStyleException(){
        $xml  = '<?xml version="1.0" encoding="utf-8" ?>';
        $xml .= '<ServiceExceptionReport>A</ServiceExceptionReport>';

        try{
          $parser = WmsCapabilitiesParser::create($xml);
        }catch(ParsingException $E){
            $this->assertEquals("A",$E->getMessage()); 
            return;
        }
        $this->fail();
    }
}
