<?php
namespace Mapbender\DrupalIntegrationBundle;

use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\DrupalIntegrationBundle\Security\Factory\DrupalFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * DrupalIntegrationBundle.
 *
 * @author Christian Wygoda
 */
class MapbenderDrupalIntegrationBundle extends MapbenderBundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new DrupalFactory());
    }
}
