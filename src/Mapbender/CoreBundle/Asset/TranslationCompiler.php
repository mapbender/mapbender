<?php


namespace Mapbender\CoreBundle\Asset;


use Symfony\Component\Templating\EngineInterface;

/**
 * Compiles application translations for frontend consumption.
 * Output is the initializer for the global Mapbender.i18n JavaScript object.
 *
 * Registered in container as mapbender.asset_compiler.translations
 */
class TranslationCompiler
{
    /** @var EngineInterface */
    protected $templateEngine;

    /**
     * @param EngineInterface $templateEngine
     */
    public function __construct(EngineInterface $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param string[] $inputs names of json.twig files
     * @return string JavaScript initialization code
     */
    public function compile($inputs)
    {
        $translations = array();
        foreach ($inputs as $transAsset) {
            $renderedTranslations = json_decode($this->templateEngine->render($transAsset), true);
            $translations = array_merge($translations, $renderedTranslations);
        }
        $translationsJson = json_encode($translations, JSON_FORCE_OBJECT);
        $jsLogic = $this->templateEngine->render($this->getTemplate());
        return $jsLogic . "\nMapbender.i18n = {$translationsJson};";
    }

    /**
     * @return string a twig path
     */
    protected function getTemplate()
    {
        return '@MapbenderCoreBundle/Resources/public/mapbender.trans.js';
    }
}
