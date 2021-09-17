<?php


namespace FOM\UserBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Extracts the configured user entity class name from 'security' extension
 * configuration (looking for an entity-type user provider) and forwards it
 * to a new container parameter.
 */
class ForwardUserEntityClassPass implements CompilerPassInterface
{
    /** @var string */
    protected $default;
    /** @var string */
    protected $targetParameterKey;

    /**
     * @param string $targetParameterKey
     * @param string $default
     */
    public function __construct($targetParameterKey, $default)
    {
        $this->targetParameterKey = $targetParameterKey;
        $this->default = $default;
    }

    public function process(ContainerBuilder $container)
    {
        $securityConfig = $container->getExtensionConfig('security');
        if ($securityConfig && !isset($securityConfig['providers'])) {
            // unravel nested extension config
            $securityConfig = array_values($securityConfig);
            $securityConfig = $securityConfig[0];
        }
        $entityProviderConfig = array();
        if (!empty($securityConfig['providers'])) {
            foreach ($securityConfig['providers'] as $name => $providerConfig) {
                if (\is_array($providerConfig) && isset($providerConfig['entity'])) {
                    $entityProviderConfig = $providerConfig['entity'];
                    break;
                }
            }
        }
        if (isset($entityProviderConfig['class'])) {
            $userEntityName = $entityProviderConfig['class'];
        } else {
            $userEntityName = $this->default;
        }
        if (!\class_exists($userEntityName)) {
            throw new \LogicException("User entity class {$userEntityName} does not exist.");
        }
        $container->setParameter($this->targetParameterKey, $userEntityName);
    }
}
