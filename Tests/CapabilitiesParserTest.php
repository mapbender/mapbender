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

        $layer = $wms->getLayer()->first();
        $this->assertSame("The Title",$layer->getTitle(), "layertitle is wrong");
        $this->assertSame("TheLayer",$layer->getName(),"layername is wrong");
//not implemented yet        $this->assertSame("EPSG:4326",$layer->getSRS(),"epsg is wrong");
//not implemented yet        $this->assertSame(null,$layer->getBBox(),"BBOx is wrong");

    }

    public function testMinimalInvalidNoName(){
        // names are important but if they are missing they should not cause problems 
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimalinvalid.noname.xml"));
        $parser  = new CapabilitiesParser($data);
        try {
            $wms = $parser->getWMSService();
        }
        catch(Exception $E){
            $this->assertSame("",$wms->getName());
        }

    }

    public function testLayersRootLayerOnly(){
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimal.singlelayer.xml"));
        $parser  = new CapabilitiesParser($data);
        $wms = $parser->getWMSService();
        $this->assertEquals(1,$wms->getLayer()->count());

        $rootLayer = $wms->getLayer()->get(0);
        $this->assertSame("The Title",$rootLayer->getTitle(),"Root layer title irsd wrong"); 
        $this->assertSame("TheLayer",$rootLayer->getName(), "Root Layer Name is wrong"); 
        $this->assertSame("A Layerabstract",$rootLayer->getAbstract(),"Root Layer abstract is wrong" );
        # The root layer itself has no sublayers
        $this->assertEquals(0,$rootLayer->getlayer()->count(), "Root Layer does not have 0 sub layers");
    }

    public function testGetMap(){
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimal.singlelayer.xml"));
        $parser  = new CapabilitiesParser($data);
        $wms = $parser->getWMSService();
        $this->assertEquals(1,$wms->getLayer()->count());

        $this->assertSame("image/png",$wms->getDefaultGetMapFormat());
        $this->assertSame("http://example.com/ohmyawms",$wms->getGetMapGet());

        $rootLayer = $wms->getRootLayer();
        $this->assertEquals("EPSG:4326",$rootLayer->getDefaultSrs());
        $this->assertEquals("-10.4 35.7 -180 180",$rootLayer->getLatLonBounds());
    }
    
}
