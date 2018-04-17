<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;


/**
 * Pseudo-compiler pass that persists the information WHEN the container has been compiled.
 * This information is not accessible via standard Symfony machinery, even though it is generated and used internally.
 */
class ContainerUpdateTimestampPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $now = microtime(true);
        $container->setParameter('container.compilation_timestamp_float', $now);
    }
}
