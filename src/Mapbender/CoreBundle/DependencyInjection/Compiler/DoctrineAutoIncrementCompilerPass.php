<?php

namespace Mapbender\CoreBundle\DependencyInjection\Compiler;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DoctrineAutoIncrementCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition('doctrine.orm.default_configuration')
            ->addMethodCall(
                'setIdentityGenerationPreferences',
                [
                    [
                        PostgreSQLPlatform::class => ClassMetadata::GENERATOR_TYPE_SEQUENCE,
                    ],
                ]
            )
        ;
    }
}
