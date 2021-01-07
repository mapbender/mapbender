<?php


namespace Mapbender\CoreBundle\DependencyInjection\Compiler;


use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Mapbender knows its own version and branding.
 *
 * What Mapbender doesn't know is project branding and versioning.
 *
 * This pass detects and uses project branding, if present, to allow overrides of the default Mapbender branding.
 * The result is written into the container and also into some legacy variables ('fom.server_version' et al, also
 * a twig global) for backwards compatibility with old templates.
 *
 * The result is a hopefully happy conglomerate of (completely populated) container parameters:
 * * mapbender.version          (set in CoreBundle/Resources/config/mapbender.yml)
 * * mapbender.branding.name    (set in CoreBundle/Resources/config/mapbender.yml)
 * * mabbender.branding.logo    (web-relative path; set in CoreBundle/Resources/config/mapbender.yml)
 * * branding.project_name      (override in parameters.yml; defaults to %mapbender.branding.name% if empty)
 * * branding.project_version   (override in parameters.yml; defaults to %mapbender.version% if empty)
 * * branding.logo              (override in parameters.yml; defaults to %mapbender.branding.logo% if empty)
 *
 * Most frontend areas should display project branding to support customization.
 *
 * The mapbender.* paramters will remain available. They will mostly be useful for self-identification in debugging,
 * logging and other internal machinery.
 */
class ProvideBrandingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $name = $this->selectProjectName($container);
        $version = $this->selectProjectVersion($container);
        $logo = $this->selectLogo($container);
        $container->setParameter('branding.project_name', $name);
        $container->setParameter('branding.project_version', $version);
        $container->setParameter('branding.logo', $logo);
        $this->forwardSelectionToFom($container, $name, $version, $logo);
    }

    public static function selectProjectName(ContainerInterface $container)
    {
        // project wins, unless it's empty
        if ($container->hasParameter('branding.project_name')) {
            $projectValue = $container->getParameter('branding.project_name');
            if ($projectValue) {
                return $projectValue;
            }
        }
        $mbName = $container->getParameter('mapbender.branding.name');
        $fomOverrideName = ArrayUtil::getDefault(static::getFomParameter($container), 'server_name', null);
        // disregard any historically used 'Mapbender' or 'Mapbender3' brandings from fom : server_name
        if (empty($fomOverrideName) || preg_match('#^\s*mapbender\d?\s*$#', $fomOverrideName)) {
            return $mbName;
        } else {
            // fom : server_name is sufficiently not "Mapbender"-ish, thus sufficiently likely to be a deliberately
            //       chosen display string in the current project.
            return $fomOverrideName;
        }
    }

    public static function selectProjectVersion(ContainerInterface $container)
    {
        // project wins, unless it's empty
        if ($container->hasParameter('branding.project_version')) {
            $projectValue = $container->getParameter('branding.project_version');
            if ($projectValue) {
                return $projectValue;
            }
        }
        $mbVersion = $container->getParameter('mapbender.version');
        $fomOverrideVersion = ArrayUtil::getDefault(static::getFomParameter($container), 'server_version', null);
        // disregard any historically used Mapbender versions (3.0.[<=7].[<=9] or "3.0pre2") from fom : server_version
        if (empty($fomOverrideVersion) || preg_match('#^\s*3\.0((\.[0-7]\.\d)|(pre2))\s*$#', $fomOverrideVersion)) {
            return $mbVersion;
        } else {
            return $fomOverrideVersion;
        }
    }

    public static function selectLogo(ContainerInterface $container)
    {
        // project wins, unless it's empty
        if ($container->hasParameter('branding.logo')) {
            $projectValue = $container->getParameter('branding.logo');
            if ($projectValue) {
                return $projectValue;
            }
        }
        $mbLogo = $container->getParameter('mapbender.branding.logo');
        $fomOverrideLogo = ArrayUtil::getDefault(static::getFomParameter($container), 'server_logo', null);
        $historicalLogos = array(
            // these are all logo names ever referenced in parameters.yml.dist over the entire github
            // history of mapbender-starter
            // hint: git log -p parameters.yml.dist | grep -P '^[+-]\s*server_logo:' | awk '{print $3}' | sort -u
            'bundles/mapbendercore/image/logo_mb3.png',
            'bundles/mapbendercore/image/logo_mb.png',
            'bundles/mapbendercore/image/mapbender-logo.png',
            'bundles/web/mapbendermanager/logo.png',
        );

        // disregard any historically used logos from fom : server_logo
        if (empty($fomOverrideLogo) || in_array($fomOverrideLogo, $historicalLogos)) {
            return $mbLogo;
        } else {
            return $fomOverrideLogo;
        }
    }

    public static function forwardSelectionToFom(ContainerBuilder $container, $name, $version, $logo)
    {
        $fomParamReplacements = array(
            'server_name' => $name,
            'server_version' => $version,
            'server_logo' => $logo,
        );

        $fomParam = static::getFomParameter($container);
        $mergedFomParam = array_replace($fomParam, $fomParamReplacements);
        $container->setParameter('fom', $mergedFomParam);

        // Forward to twig global; this mirrors legay Mapbender Starter config.yml, which adds the value of the 'fom'
        // parameter as twig global 'fom' by expansion
        // Because we just modified the 'fom' parameter, we must update the Twig global as well
        $twigDefinition = $container->getDefinition('twig');
        $twigMethodCalls = $twigDefinition->getMethodCalls();
                $addGlobalMethodCallFound = false;
        foreach ($twigMethodCalls as &$methodCall) {
            /** @see \Twig_Environment::addGlobal() */
            if ($methodCall[0] == 'addGlobal' && !empty($methodCall[1][0]) && $methodCall[1][0] == 'fom') {
                $methodCall[1][1] = array_replace($methodCall[1][1] ?: array(), $mergedFomParam);
                $twigDefinition->setMethodCalls($twigMethodCalls);
                $addGlobalMethodCallFound = true;
                break;
            }
        }
        if (!$addGlobalMethodCallFound) {
            // not added as twig global, but (still) required in many templates => add it
            $twigDefinition->addMethodCall('addGlobal', array(
                'fom',
                $mergedFomParam,
            ));
        }
    }

    public static function getFomParameter(ContainerInterface $container, $default = array())
    {
        return $container->hasParameter('fom') ? ($container->getParameter('fom') ?: $default) : $default;
    }
}
