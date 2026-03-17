<?php


namespace Mapbender\CoreBundle\Asset;


use Assetic\Asset\FileAsset;
use Assetic\Asset\StringAsset;

/**
 * Locates and merges JavaScript assets for applications.
 * Default implementation for service mapbender.asset_compiler.js
 * @since v3.0.8.5-beta1
 */
class JsCompiler extends AssetFactoryBase
{
    /**
     * Perform simple concatenation of all input assets. Some uniquification will take place.
     *
     * @param (FileAsset|StringAsset)[] $inputs
     * @param bool $debug to enable file input markers
     * @return string
     */
    public function compile($inputs, bool $debug, ?string $sourceMapRoute)
    {
        return $this->concatenateContents($inputs, $debug ? $sourceMapRoute : null);
    }

    protected function getMigratedReferencesMapping()
    {
        return array(
            '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js' => '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js',
            '@MapbenderCoreBundle/Resources/public/widgets/mapbender.checkbox.js' => '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js',
            '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js' => '@MapbenderCoreBundle/Resources/public/widgets/dropdown.js',
            '@FOMCoreBundle/Resources/public/js/widgets/popup.js' => '@MapbenderCoreBundle/Resources/public/widgets/mapbender.popup.js',
            '@MapbenderCoreBundle/Resources/public/widgets/fom-popup.js' => '@MapbenderCoreBundle/Resources/public/widgets/mapbender.popup.js',
            '@FOMCoreBundle/Resources/public/js/widgets/collection.js' => '@MapbenderManagerBundle/Resources/public/form/collection.js',
            '@FOMCoreBundle/Resources/public/js/components.js' => '@MapbenderManagerBundle/Resources/public/components.js',
            '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js' => '@MapbenderCoreBundle/Resources/public/widgets/sidepane.js',
            '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js' => '@MapbenderCoreBundle/Resources/public/widgets/tabcontainer.js',
            // Select2: sourcing from vendor makes i18n sub-path inaccessible; this is ok, because the legacy robloach build does not contain i18n either
            '/components/select2/select2-built.js' => '/../vendor/select2/select2/dist/js/select2.js',
            // Bootstrap colorpicker (from abandoned debugteam fork) absorbed into Mapbender, pre-provided in template
            '/components/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js' => array(),
        );
    }
}
