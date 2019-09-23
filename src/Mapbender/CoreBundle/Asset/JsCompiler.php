<?php


namespace Mapbender\CoreBundle\Asset;

/**
 * Locates and merges JavaScript assets for applications.
 * Registered in container as mapbender.asset_compiler.js
 */
class JsCompiler extends AssetFactoryBase
{
    /**
     * Mark assets as moved, so refs can be rewritten
     * This is a ~curated list, currently not intended for configurability.
     * @var string[]
     */
    protected $migratedRefs = array(
        '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js' => '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js',
        '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js' => '@MapbenderCoreBundle/Resources/public/widgets/dropdown.js',
        '@FOMCoreBundle/Resources/public/js/widgets/popup.js' => '@MapbenderCoreBundle/Resources/public/widgets/fom-popup.js',
        '@FOMCoreBundle/Resources/public/js/widgets/collection.js' => '@MapbenderManagerBundle/Resources/public/form/collection.js',
        '@FOMCoreBundle/Resources/public/js/components.js' => '@MapbenderManagerBundle/Resources/public/components.js',
        '@FOMCoreBundle/Resources/public/js/frontend/sidepane.js' => '@MapbenderCoreBundle/Resources/public/widgets/sidepane.js',
        '@FOMCoreBundle/Resources/public/js/frontend/tabcontainer.js' => '@MapbenderCoreBundle/Resources/public/widgets/tabcontainer.js',
    );

    /**
     * Perform simple concatenation of all input assets. Some uniquification will take place.
     *
     * @param (FileAsset|StringAsset)[] $inputs
     * @param bool $debug to enable file input markers
     * @return string
     */
    public function compile($inputs, $debug)
    {
        return $this->concatenateContents($inputs, $debug);
    }

    /**
     * @param string $input reference to an asset file
     * @return string resolved absolute path to file
     */
    protected function locateAssetFile($input)
    {
        while (!empty($this->migratedRefs[$input])) {
            $input = $this->migratedRefs[$input];
        }
        return parent::locateAssetFile($input);
    }
}
