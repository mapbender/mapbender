<?php
/*
* @package bkg_testing
* @author Karim Malhas <karim@malhas.de>
*/

use MB\WMSBundle\Components\CapabilitiesParser;
/**
 *   Tests the CapabilitiesParser. Note that te tests are coupled to the testdata somewhaty tightly. This is on purpose
 *   to keep the tests simple
 */
class CapabilitiesParserTest extends PHPUnit_Framework_TestCase {


    public function testMinimal(){

        $keyword = new MB\CoreBundle\Entity\Keyword();
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimal.singlelayer.xml"));
        $parser  = new CapabilitiesParser($data);
        $wms = $parser->getWMSService();
        $this->assertSame("OGC:WMS",$wms->getName(),"Name is wrong");
        $this->assertSame("The example.com Test WMS",$wms->getTitle(),  "title is wrong");
        $this->assertEquals(1,count($wms->getLayer()),"layercount is wrong");

        $layer = $wms->getLayers()->first();
        $this->assertSame("The Title",$layer->getTitle(), "layertitle is wrong");
        $this->assertSame("TheLayer",$layer->getName(),"layername is wrong");
        $this->assertSame("EPSG:4326",$layer->getSRS(),"epsg is wrong");
        $this->assertSame(null,$layer->getBBox(),"BBOx is wrong");

    }

    public function testMinimalInvalidNoName(){
        
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimalinvalid.noname.xml"));
        $parser  = new CapabilitiesParser($data);
        try {
            $wms = $parser->getWMSService();
        }
        catch(Exception $E){
            return true;
        }
        $this->assertSame("",$wms->getName());
        $this->fail("Expected Exception not thrown");


    }
}
