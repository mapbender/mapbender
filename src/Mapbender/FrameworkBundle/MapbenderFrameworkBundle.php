<?php


namespace Mapbender\FrameworkBundle;


use Mapbender\FrameworkBundle\DependencyInjection\Compiler\ConfigCheckExtensionPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterApplicationTemplatesPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterDataSourcesPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterElementServicesPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterGlobalPermissionDomainsPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterIconPackagesPass;
use Mapbender\FrameworkBundle\DependencyInjection\Compiler\RegisterPermissionDomainsPass;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\Bundle\Bundle;


class MapbenderFrameworkBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);
        $configLocator = new FileLocator(__DIR__ . '/Resources/config');
        $loader = new XmlFileLoader($container, $configLocator);
        $loader->load('services.xml');
        $loader->load('symfony.xml');
        // Register service elements
        // Run pass with reduced priority, so it happens after non-service inventory building has completed
        $container->addCompilerPass(new RegisterElementServicesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
        // Forward available application template classes to registry service
        /** @see \Mapbender\CoreBundle\Component\Source\TypeDirectoryService */
        $container->addCompilerPass(new RegisterDataSourcesPass('mapbender.source.typedirectory.service'));
        /** @see \Mapbender\CoreBundle\Command\ConfigCheckCommand */
        $container->addCompilerPass(new ConfigCheckExtensionPass('Mapbender\CoreBundle\Command\ConfigCheckCommand'));
        /** @see \Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry */
        $container->addCompilerPass(new RegisterApplicationTemplatesPass('mapbender.application_template_registry'));
        // Forward available icon packages to icon index
        /** @see \Mapbender\FrameworkBundle\Component\IconIndex */
        $container->addCompilerPass(new RegisterIconPackagesPass('mapbender.icon_index'));

        /** @see \FOM\UserBundle\Security\Permission\PermissionManager */
        $container->addCompilerPass(new RegisterPermissionDomainsPass('fom.security.permission_manager'));
        /** @see \FOM\UserBundle\Security\Permission\ResourceDomainInstallation */
        $container->addCompilerPass(new RegisterGlobalPermissionDomainsPass('fom.security.resource_domain.installation'));

    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        return null;
    }
}
