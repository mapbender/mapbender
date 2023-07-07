<?php

namespace Mapbender\WmsBundle\Tests;

use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class SourceUpdateTest extends KernelTestCase
{
    const TEST_DATA_PATH = __DIR__ ."/testdata/wms-1.1.1-getcapabilities.minimal.severallayers.xml";

    private $importer;
    private $data;

    protected function setUp(): void
    {
        parent::setUp();
        self::bootKernel();
        $this->importer = self::$kernel->getContainer()->get('mapbender.importer.source.wms.service');
        $this->data = $this->maskAmpersands(file_get_contents(self::TEST_DATA_PATH));
    }

    public function testEquality()
    {
        list($target, $source) = $this->prepareSources();

        $this->assertTrue($target->getRootlayer()->equivalent($source->getRootlayer()));
        $this->importer->updateSourceLayers($target, $source);
        $this->assertTrue($target->getRootlayer()->equivalent($source->getRootlayer()));
    }

    public function testNewSublayer()
    {
        list($target, $source) = $this->prepareSources();

        $testsource = new WmsLayerSource();
        $testsource->setName("new name");
        $source->getRootlayer()->addSublayer($testsource);

        $this->compareAndSyncLayers($target, $source);
    }

    public function testDeletedSublayer()
    {
        list($target, $source) = $this->prepareSources();

        $rootLayer =  $source->getRootlayer();
        $source->getRootlayer()->removeSublayer("SubLayer1");

        $this->compareAndSyncLayers($target, $source);
    }

    private function prepareSources(): array
    {
        $target = $this->importer->parseResponseContent($this->data);
        $source = $this->importer->parseResponseContent($this->data);
        return [$target, $source];
    }

    private function compareAndSyncLayers(WmsSource $target, WmsSource $source)
    {
        $this->assertFalse($target->getRootlayer()->equivalent($source->getRootlayer()));
        $this->importer->updateSourceLayers($target, $source);
        $rootLayer =  $source->getRootlayer();
        $this->assertTrue($target->getRootlayer()->equivalent($source->getRootlayer()));
    }

    private function maskAmpersands($xmlString): string
    {
        $pattern = '/(?<!=)&(?![a-zA-Z0-9#]+;)/';
        $replacement = '&amp;';
        return preg_replace($pattern, $replacement, $xmlString);
    }
}
