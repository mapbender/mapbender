<?php


namespace Mapbender\CoreBundle\Component;

/**
 * Common interface for classes (Elements, Applications, Templates) that require certain assets to function.
 */
interface IAssetDependent
{
    /**
     * Get lists of paths to required assets.
     *
     * Return should be a 2D array with top-level keys 'js', 'css' and 'trans'.
     * Each sub-array lists the required assets.
     *
     * A plain name is interpreted as local to the provider's bundle.
     *
     * Use bundle-style paths ('@MapbenderSomethingBundle/Resources/public/...') to reference assets from a different
     * bundle. Use absolute paths ('/components/something/...') to reference a file in the web folder.
     *
     * @return string[][]
     */
    public function getAssets();
}
