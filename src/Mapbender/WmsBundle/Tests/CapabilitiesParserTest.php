<?php
/*
* @package bkg_testing
* @author Karim Malhas <karim@malhas.de>
*/

/**
 *   Tests the WmsCapabilitiesParser. Note that te tests are coupled to the testdata somewhaty tightly. This is on purpose
 *   to keep the tests simple
 */
class WmsCapabilitiesParserTest extends \Symfony\Bundle\FrameworkBundle\Test\KernelTestCase
{
    public function testMinimal(){
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimal.singlelayer.xml"));
        $wms = $this->getImporter()->parseResponseContent($data);
        $this->assertSame("OGC:WMS",$wms->getName(),"Name is wrong");
        $this->assertSame("The example.com Test WMS",$wms->getTitle(),  "title is wrong");
        $this->assertEquals(1,count($wms->getLayers()),"layercount is wrong");

        $layer = $wms->getLayers()->first();
        $this->assertSame("The Title",$layer->getTitle(), "layertitle is wrong");
        $this->assertSame("TheLayer",$layer->getName(),"layername is wrong");
//not implemented yet        $this->assertSame("EPSG:4326",$layer->getSRS(),"epsg is wrong");
//not implemented yet        $this->assertSame(null,$layer->getBBox(),"BBOx is wrong");

    }

    public function testLayersRootLayerOnly(){
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimal.singlelayer.xml"));
        $wms = $this->getImporter()->parseResponseContent($data);
        $this->assertEquals(1,$wms->getLayers()->count());

        $rootLayer = $wms->getRootlayer();
        $this->assertSame("The Title",$rootLayer->getTitle(),"Root layer title irsd wrong");
        $this->assertSame("TheLayer",$rootLayer->getName(), "Root Layer Name is wrong");
        $this->assertSame("A Layerabstract",$rootLayer->getAbstract(),"Root Layer abstract is wrong" );
        # The root layer itself has no sublayers
        $this->assertEquals(0,$rootLayer->getSublayer()->count(), "Root Layer does not have 0 sub layers");
    }

    public function testGetMap(){
        $data = file_get_contents((dirname(__FILE__) ."/testdata/wms-1.1.1-getcapabilities.minimal.singlelayer.xml"));
        $wms = $this->getImporter()->parseResponseContent($data);
        $this->assertEquals(1,$wms->getLayers()->count());

        //$this->assertSame("image/png",$wms->getDefaultRequestGetMapFormat());
        $array = $wms->getGetMap()->getFormats();
        $this->assertSame("image/png",$array[0]);
        $this->assertSame("http://example.com/ohmyawms",$wms->getGetMap()->getHttpGet());

        $rootLayer = $wms->getRootlayer();
        $array = $rootLayer->getSrs();

        $this->assertEquals("EPSG:4326", $array[0]);
        $bb = $rootLayer->getLatlonBounds();
        $strbb = $bb->getMinx()." ".$bb->getMiny()." ".$bb->getMaxx()." ".$bb->getMaxy();
        $this->assertEquals("-10.4 35.7 -180 180",$strbb);
    }

    protected static function getImporter()
    {
        if (!self::$booted) {
            self::bootKernel();
        }
        /** @var \Mapbender\WmsBundle\Component\Wms\Importer $importer*/
        $importer = self::$kernel->getContainer()->get('mapbender.importer.source.wms.service');
        return $importer;
    }

}
