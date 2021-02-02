<?php


namespace Mapbender\CoreBundle\Asset;

use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Templating\EngineInterface;

/**
 * Locates and merges JavaScript assets for applications.
 * Default implementation for service mapbender.asset_compiler.js
 * @since v3.0.8.5-beta1
 */
class JsCompiler extends AssetFactoryBase
{
    /** @var EngineInterface */
    protected $templateEngine;

    public function __construct(EngineInterface $templateEngine, FileLocatorInterface $fileLocator, $webDir, $bundleClassMap)
    {
        parent::__construct($fileLocator, $webDir, $bundleClassMap);
        $this->templateEngine = $templateEngine;
    }

    /**
     * Perform simple concatenation of all input assets. Some uniquification will take place.
     *
     * @param (FileAsset|StringAsset)[] $inputs
     * @param string|null $configSlug for emission of application initialzation script
     * @param bool $debug to enable file input markers
     * @return string
     */
    public function compile($inputs, $configSlug, $debug)
    {
        return $this->concatenateContents($inputs, $debug);
    }

    protected function getMigratedReferencesMapping()
    {
        return array(
            '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js' => '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js',
            '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js' => '@MapbenderCoreBundle/Resources/public/widgets/dropdown.js',
            '@FOMCoreBundle/Resources/public/js/widgets/popup.js' => '@MapbenderCoreBundle/Resources/public/widgets/fom-popup.js',
            '@FOMCoreBundle/Resources/public/js/widgets/collection.js' => '@MapbenderManagerBundle/Resources/public/form/collection.js',
            '@FOMCoreBundle/Resources/public/js/components.js' => '@MapbenderManagerBundle/Resources/public/components.js',
            '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js' => '@MapbenderCoreBundle/Resources/public/widgets/sidepane.js',
            '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js' => '@MapbenderCoreBundle/Resources/public/widgets/tabcontainer.js',
            // update for reliance on robloach/component-installer
            '/components/jquerydialogextendjs/jquerydialogextendjs-built.js' => '/../vendor/wheregroup/jquerydialogextendjs/build/jquery.dialogextend.min.js',
            // Select2: sourcing from vendor makes i18n sub-path inaccessible; this is ok, because the legacy robloach build does not contain i18n either
            '/components/select2/select2-built.js' => '/../vendor/select2/select2/dist/js/select2.js',
        );
    }
}
