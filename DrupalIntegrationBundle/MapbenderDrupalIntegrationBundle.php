<?php

/*
 * This file is part of the Mapbender 3 project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
