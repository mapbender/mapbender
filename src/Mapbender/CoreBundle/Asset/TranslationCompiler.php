<?php


namespace Mapbender\CoreBundle\Asset;


use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Compiles application translations for frontend consumption.
 * Output is the initializer for the global Mapbender.i18n JavaScript object.
 *
 * Registered in container as mapbender.asset_compiler.translations
 */
class TranslationCompiler
{
    /** @var TranslatorInterface|TranslatorBagInterface */
    protected $translator;
    /** @var EngineInterface */
    protected $templateEngine;
    /** @var string[]|null */
    protected $allMessages;

    /**
     * @param TranslatorInterface $translator
     * @param EngineInterface $templateEngine
     */
    public function __construct(TranslatorInterface $translator, EngineInterface $templateEngine)
    {
        if (!($translator instanceof TranslatorBagInterface)) {
            throw new \InvalidArgumentException("Given translator does not implement required TranslatorBagInterface");
        }
        $this->translator = $translator;
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param string[] $inputs names of json.twig files
     * @return string JavaScript initialization code
     */
    public function compile($inputs)
    {
        $translations = array();
        foreach ($inputs as $input) {
            if (preg_match('/\.json\.twig$/', $input)) {
                $values = $this->extractFromTemplate($input);
            } else {
                $values = $this->translatePattern($input);
            }
            $translations += $values;
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

    /**
     * @param string $template
     * @return string[]
     */
    protected function extractFromTemplate($template)
    {
        return json_decode($this->templateEngine->render($template), true);
    }

    /**
     * @param string $input translation key or prefix pattern ending in '.*'
     * @return string[]
     */
    protected function translatePattern($input)
    {
        $values = array();
        if (preg_match('/\*$/', $input)) {
            $wildcardPrefix = rtrim($input, '*');
            if (!$wildcardPrefix || false !== strpos($wildcardPrefix, '*')) {
                throw new \RuntimeException("Invalid translation key input " . print_r($input, true));
            }
            if ($this->allMessages === null) {
                $this->allMessages = $this->translator->getCatalogue()->all('messages');
            }
            foreach ($this->allMessages as $translationKey => $message) {
                if (0 === strpos($translationKey, $wildcardPrefix)) {
                    $values[$translationKey] = $message;
                }
            }
            if (!$values) {
                throw new \LogicException("No matches for translation key prefix " . print_r($input, true));
            }
        } else {
            $translated = $this->translator->trans($input);
            if ($translated === $input) {
                throw new \LogicException("Untranslatable value " . print_r($input, true));
            }
            $values[$input] = $this->translator->trans($input);
        }
        return $values;
    }
}
