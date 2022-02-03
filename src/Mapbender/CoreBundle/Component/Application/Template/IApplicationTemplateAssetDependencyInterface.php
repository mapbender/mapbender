<?php


namespace Mapbender\CoreBundle\Component\Application\Template;


use Mapbender\CoreBundle\Entity\Application;

interface IApplicationTemplateAssetDependencyInterface
{
    /**
     * Should return the list of assets of given $type that are required for the template to function.
     *
     * Supportable $type values are 'css', 'js', 'trans'.
     *
     * 'js' and 'css' entries should be either
     * 1) bundle-qualified paths starting with '@'; e.g:
     *    '@SpecialTemplateBundle/Resources/public/template/special-template.css'
     * 2) web-root-anchored paths starting with '/'; e.g:
     *    '/components/underscore/underscore-min.js'
     *
     * 'trans' entries are expected to name .json.twig files and must be
     * twig-compatible (e.g. Somebundle:[optional-subpath under Resources/views:]translations.json.twig
     *
     * (unqualified bundle-local resource paths are technically possible but highly discouraged because they break
     *  inheritance)
     *
     * @param string $type one of 'css', 'js' or 'trans'
     * @return string[]
     */
    public function getAssets($type);

    /**
     * Should return a list of file references containing sass variables definitions
     *
     * @param Application $application
     * @return string[]
     */
    public function getSassVariablesAssets(Application $application);

    /**
     * Should return 'late' assets, to be loaded at the very end, particularly after all Element assets.
     * Semantics are the same as for @see getAssets
     *
     * @param string $type one of 'css', 'js' or 'trans'
     * @return string[]
     */
    public function getLateAssets($type);
}
