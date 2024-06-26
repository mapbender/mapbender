<?php
namespace Mapbender\CoreBundle;

use Mapbender\CoreBundle\DependencyInjection\Compiler\ContainerUpdateTimestampPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\MapbenderYamlCompilerPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\ProvideBrandingPass;
use Mapbender\CoreBundle\DependencyInjection\Compiler\ProvideCookieConsentGlobalPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;


/**
 * @author Christian Wygoda
 */
class MapbenderCoreBundle extends Bundle
{

    /**
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $now = new \DateTime('now');
        $container->setParameter("mapbender.cache_creation", $now->format('c'));

        $this->loadConfigs($container);

        $container->addCompilerPass(new MapbenderYamlCompilerPass());
        $container->addCompilerPass(new ContainerUpdateTimestampPass());
        $container->addCompilerPass(new ProvideBrandingPass());

        $container->addCompilerPass(new ProvideCookieConsentGlobalPass());
    }

    protected function loadConfigs(ContainerBuilder $container)
    {
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $xmlLoader = new XmlFileLoader($container, $configLocator);
        $yamlLoader = new YamlFileLoader($container, $configLocator);
        foreach ($this->getConfigs() as $configName) {
            if (preg_match('#\.xml$#', $configName)) {
                $xmlLoader->load($configName);
                $resourcePath = $xmlLoader->getLocator()->locate($configName);
            } else {
                $yamlLoader->load($configName);
                $resourcePath = $yamlLoader->getLocator()->locate($configName);
            }
            $container->addResource(new FileResource($resourcePath));
        }
    }

    /**
     * @return string[]
     */
    protected function getConfigs()
    {
        return array(
            'services.xml',
            'controllers.xml',
            'commands.xml',
            'mapbender.yaml',
            'constraints.yaml',
            'elements.xml',
            'templates.xml',
        );
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return null;
    }
}
