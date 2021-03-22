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
        $content = $this->concatenateContents($inputs, $debug);
        if ($configSlug) {
            $content .= $this->renderAppLoader($configSlug);
        }
        return $content;
    }

    /**
     * Returns JavaScript code with final client-side application initialization.
     * This should be the very last bit, following all other JavaScript definitions
     * and initializations.
     * @param string $slug
     * @return string
     */
    protected function renderAppLoader($slug)
    {
        $viewParams = array(
            'slug' => $slug,
            // fake an application entity (legacy twig)
            'application' => array(
                'slug' => $slug,
            ),
        );
        $appLoaderTemplate = '@MapbenderCoreBundle/Resources/views/application.config.loader.js.twig';
        return $this->templateEngine->render($appLoaderTemplate, $viewParams);
    }

    protected function getMigratedReferencesMapping()
    {
        return array(
            '@FOMCoreBundle/Resources/public/js/widgets/checkbox.js' => array(
                '@MapbenderCoreBundle/Resources/public/widgets/mapbender.checkbox.js',
                '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js',
            ),
            '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js' => array(
                '@MapbenderCoreBundle/Resources/public/widgets/mapbender.checkbox.js',
                '@MapbenderCoreBundle/Resources/public/widgets/checkbox.js',
            ),
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
            // vis-ui.js-built.js is a concatenation of bunch of files. Expand to list of individual file
            // references in vendor. This allows mixed requirement of built and individual files without
            // emitting duplicates
            '/components/vis-ui.js/vis-ui.js-built.js' => array(
                // @see https://github.com/mapbender/vis-ui.js/blob/0.2.84/composer.json#L36
                '/../vendor/mapbender/vis-ui.js/src/js/utils/DataUtil.js',          // @deprecated
                '/../vendor/mapbender/vis-ui.js/src/js/utils/fn.formData.js',
                '/../vendor/mapbender/vis-ui.js/src/js/utils/StringHelper.js',      // @deprecated
                '/../vendor/mapbender/vis-ui.js/src/js/elements/confirm.dialog.js',
                '/../vendor/mapbender/vis-ui.js/src/js/elements/data.result-table.js',
                // <input type="date"> replacement for legacy browsers only; @see https://caniuse.com/input-datetime
                '/../vendor/mapbender/vis-ui.js/src/js/elements/date.selector.js',
                '/../vendor/mapbender/vis-ui.js/src/js/elements/popup.dialog.js',
                '/../vendor/mapbender/vis-ui.js/src/js/elements/tab.navigator.js',
                '/../vendor/mapbender/vis-ui.js/src/js/jquery.form.generator.js',
            ),
        );
    }
}
