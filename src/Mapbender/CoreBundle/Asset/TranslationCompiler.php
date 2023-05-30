<?php


namespace Mapbender\CoreBundle\Asset;


use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig;

/**
 * Compiles application translations for frontend consumption.
 * Output is the initializer for the global Mapbender.i18n JavaScript object.
 *
 * Default implementation for service mapbender.asset_compiler.translations
 * @since v3.0.8.5-beta1
 */
class TranslationCompiler
{
    /** @var TranslatorInterface|TranslatorBagInterface */
    protected $translator;
    /** @var Twig\Environment */
    protected $templateEngine;
    /** @var string[]|null */
    protected $allMessages;

    protected $treatTemplatesAsOptional = true;

    /**
     * @param TranslatorInterface $translator
     * @param Twig\Environment $templateEngine
     */
    public function __construct(TranslatorInterface $translator, Twig\Environment $templateEngine)
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
        try {
            $rendered = $this->templateEngine->render($template);
        } catch (\InvalidArgumentException $e) {
            if ($this->treatTemplatesAsOptional) {
                return array();
            } else {
                throw $e;
            }
        }
        return json_decode($rendered, true);
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
