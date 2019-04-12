<?php


namespace Mapbender\CoreBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ProvideCookieConsentGlobalPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $paramName = 'mapbender.cookieconsent';
        $cookieConsent = !!$container->getParameter($paramName);

        $twigDefinition = $container->getDefinition('twig');
        // not added as twig global, but (still) required in many templates => add it
        $twigDefinition->addMethodCall('addGlobal', array(
            'cookieconsent',
            $cookieConsent,
        ));
    }
}
