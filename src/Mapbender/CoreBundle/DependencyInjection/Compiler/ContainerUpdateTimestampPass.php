<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


/**
 * Pseudo-compiler pass that persists the information WHEN the container has been compiled.
 * This information is not accessible via standard Symfony machinery, even though it is generated and used internally.
 *
 * The container compilation time is used to invalidate cached data after any configuration changes, including (but
 * not limited to) changes in yaml-based applications.
 */
class ContainerUpdateTimestampPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $now = microtime(true);
        $container->setParameter('container.compilation_timestamp_float', $now);
    }
}
