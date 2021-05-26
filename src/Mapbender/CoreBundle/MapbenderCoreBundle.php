<?php
namespace Mapbender\CoreBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\DependencyInjection\Compiler\AutodetectSasscBinaryPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\ContainerUpdateTimestampPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\MapbenderYamlCompilerPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\ProvideBrandingPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\ProvideCookieConsentGlobalPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\RebuildElementInventoryPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\RewriteFormThemeCompilerPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * @author Christian Wygoda
 */
class MapbenderCoreBundle extends MapbenderBundle
{

    /**
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $this->loadConfigs($container);

        $container->addCompilerPass(new MapbenderYamlCompilerPass());
        $container->addCompilerPass(new ContainerUpdateTimestampPass());
        $container->addCompilerPass(new ProvideBrandingPass());
        $container->addCompilerPass(new AutodetectSasscBinaryPass('mapbender.asset.sassc_binary_path'));

        // @todo: remove legacy form theme bridging
        //        TBD: either rely on correct starter config (and do nothing here)
        //             or SET theme here, discarding starter config
        $formThemeOldLocation = 'FOMCoreBundle:Form:fields.html.twig';
        $formThemeNewLocation = 'MapbenderCoreBundle:form:fields.html.twig';
        $container->addCompilerPass(new RewriteFormThemeCompilerPass($formThemeOldLocation, $formThemeNewLocation));
        $container->addCompilerPass(new ProvideCookieConsentGlobalPass());
        $container->addCompilerPass(new RebuildElementInventoryPass());
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
            'Mapbender\CoreBundle\Element\ApplicationSwitcher',
            'Mapbender\CoreBundle\Element\BaseSourceSwitcher',
            'Mapbender\CoreBundle\Element\ControlButton',
            'Mapbender\CoreBundle\Element\CoordinatesDisplay',
            'Mapbender\CoreBundle\Element\Copyright',
            'Mapbender\CoreBundle\Element\FeatureInfo',
            'Mapbender\CoreBundle\Element\GpsPosition',
            'Mapbender\CoreBundle\Element\HTMLElement',
            'Mapbender\CoreBundle\Element\Layertree',
            'Mapbender\CoreBundle\Element\Legend',
            'Mapbender\CoreBundle\Element\LinkButton',
            'Mapbender\CoreBundle\Element\ViewManager',
            'Mapbender\CoreBundle\Element\Map',
            'Mapbender\CoreBundle\Element\Overview',
            'Mapbender\CoreBundle\Element\POI',
            'Mapbender\CoreBundle\Element\ResetView',
            'Mapbender\CoreBundle\Element\Ruler',
            'Mapbender\CoreBundle\Element\ScaleBar',
            'Mapbender\CoreBundle\Element\ScaleDisplay',
            'Mapbender\CoreBundle\Element\ScaleSelector',
            'Mapbender\CoreBundle\Element\SearchRouter',
            'Mapbender\CoreBundle\Element\ShareUrl',
            'Mapbender\CoreBundle\Element\SimpleSearch',
            'Mapbender\CoreBundle\Element\SrsSelector',
            'Mapbender\CoreBundle\Element\ZoomBar',
            'Mapbender\CoreBundle\Element\Sketch',
        );
    }

    /**
     * @inheritdoc
     */
    public function getACLClasses()
    {
        return array(
            'Mapbender\CoreBundle\Entity\Application' => 'mb.terms.application.plural',
            'Mapbender\CoreBundle\Entity\Source' => 'mb.terms.source.plural',
        );
    }

    protected function loadConfigs(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $xmlLoader = new XmlFileLoader($container, $configLocator);
        $yamlLoader = new YamlFileLoader($container, $configLocator);
        foreach ($this->getConfigs() as $configName) {
            if (preg_match('#\.xml$#', $configName)) {
                $xmlLoader->load($configName);
            } else {
                $yamlLoader->load($configName);
            }
        }
    }

    /**
     * @return string[]
     */
    protected function getConfigs()
    {
        return array(
            'security.xml',
            'services.xml',
            'mapbender.yml',
            'constraints.yml',
            'formTypes.yml',
        );
    }

    public function getContainerExtension()
    {
        return null;
    }
}
