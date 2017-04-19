<?php
namespace Mapbender\CoreBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\DependencyInjection\Compiler\MapbenderYamlCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * CoreBundle.
 *
 * @author Christian Wygoda
 */
class MapbenderCoreBundle extends MapbenderBundle
{

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new MapbenderYamlCompilerPass());
    }

    /**
     * @inheritdoc
     */
    public function getTemplates()
    {
        return array
            (
                'Mapbender\CoreBundle\Template\Fullscreen',
                'Mapbender\CoreBundle\Template\FullscreenAlternative',
                'Mapbender\CoreBundle\Template\Responsive',
                'Mapbender\CoreBundle\Template\Classic'
                // 'Mapbender\CoreBundle\Template\Regional'
            );
    }

    /**
     * @inheritdoc
     */
    public function getElements()
    {
        return array(
            'Mapbender\CoreBundle\Element\AboutDialog',
            'Mapbender\CoreBundle\Element\ActivityIndicator',
            'Mapbender\CoreBundle\Element\BaseSourceSwitcher',
            'Mapbender\CoreBundle\Element\Button',
            'Mapbender\CoreBundle\Element\CoordinatesDisplay',
            'Mapbender\CoreBundle\Element\Copyright',
            'Mapbender\CoreBundle\Element\FeatureInfo',
            'Mapbender\CoreBundle\Element\GpsPosition',
            'Mapbender\CoreBundle\Element\HTMLElement',
            'Mapbender\CoreBundle\Element\Layertree',
            'Mapbender\CoreBundle\Element\Legend',
            'Mapbender\CoreBundle\Element\Map',
            'Mapbender\CoreBundle\Element\Overview',
            'Mapbender\CoreBundle\Element\POI',
            'Mapbender\CoreBundle\Element\PrintClient',
            'Mapbender\CoreBundle\Element\Ruler',
            'Mapbender\CoreBundle\Element\ScaleBar',
            'Mapbender\CoreBundle\Element\ScaleDisplay',
            'Mapbender\CoreBundle\Element\ScaleSelector',
            'Mapbender\CoreBundle\Element\SearchRouter',
            'Mapbender\CoreBundle\Element\SimpleSearch',
            'Mapbender\CoreBundle\Element\Sketch',
            'Mapbender\CoreBundle\Element\SrsSelector',
            'Mapbender\CoreBundle\Element\ZoomBar',
            'Mapbender\CoreBundle\Element\Redlining',
            'Mapbender\CoreBundle\Element\Logout'
        );
    }

    /**
     * @inheritdoc
     */
    public function getACLClasses()
    {
        return array(
            'Mapbender\CoreBundle\Entity\Application' => 'Application',
            'Mapbender\CoreBundle\Entity\Element' => 'Element',
            'Mapbender\CoreBundle\Entity\Source' => 'Service Source');
    }
}
