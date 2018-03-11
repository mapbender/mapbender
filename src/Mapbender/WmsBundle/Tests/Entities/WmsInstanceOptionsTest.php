<?php


namespace Mapbender\WmsBundle\Tests\Entities;


use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\Component\WmsInstanceConfigurationOptions;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
 * @group unit
 */
class WmsInstanceOptionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return WmsInstance
     */
    public static function makeBlankWmsInstance()
    {
        // this is the MINIMAL initialization of a WmsInstance so the options can
        // be generated...
        // @todo: WmsSource ctor should ensure GetMap is prepopulated
        // @todo: WmsInstance factory should ensure source is preset to given WmsSource
        // @todo: WmsInstance factory should ensure root layer instance points to source root layer
        $blankGetMap = new RequestInformation();
        $blankWmsSource = new WmsSource();
        $blankWmsSource->setGetMap($blankGetMap);
        $blankRootLayer = new WmsLayerSource();

        $blankWmsSource->addLayer($blankRootLayer);
        $blankWmsInstance = new WmsInstance();
        $blankWmsInstance->populateFromSource($blankWmsSource);
        $blankWmsInstance->setSource($blankWmsSource);

        $blankWmsInstance->getRootlayer()->setSourceItem($blankRootLayer);
        return $blankWmsInstance;
    }

    public function testInstanceConfigurationDefaultsMatchEntityDefaults()
    {
        $blankWmsInstance = $this->makeBlankWmsInstance();

        $blankInstanceOptions = new WmsInstanceConfigurationOptions();
        $generatedInstanceOptions = WmsInstanceConfigurationOptions::fromEntity($blankWmsInstance);

        $instanceDefaultArray = $generatedInstanceOptions->toArray();
        $instanceConfigurationDefaultsArray = $blankInstanceOptions->toArray();

        // if you want to dump ... this will end the phpunit run
        // while (ob_get_level()) { ob_end_clean();}
        // die(var_export($instanceDefaultArray, true) . "\n");
        // die(var_export($instanceConfigurationDefaultsArray, true) . "\n");

        $this->assertSame($instanceDefaultArray, $instanceConfigurationDefaultsArray);
    }
}
