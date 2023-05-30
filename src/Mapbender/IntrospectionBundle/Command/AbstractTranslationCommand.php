<?php


namespace Mapbender\IntrospectionBundle\Command;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Translation\TranslatorBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractTranslationCommand extends Command
{
    // NOTE: Symfony does not provide listing all available catalogs, so we have to start with a master list
    // HINT: find . -name 'messages.*.yml' -printf '%f\n' -o -name 'messages.*.xlf' -printf '%f\n' | sort -u
    /** @var string[] */
    protected $allCatalogNames = array(
        'en',
        'de',
        'es',
        'fr',
        'it',
        'nl',
        'pt',
        'ru',
        'tr',
    );

    /** @var TranslatorInterface|TranslatorBagInterface */
    protected $translator;
    /** @var string[]|false */
    protected $fallbackLocales;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
        parent::__construct(null);
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (method_exists($this->translator, 'getFallbackLocales')) {
            // Translator or DataCollectorTranslator, potentially more implementations
            // method is not part of any interfaces though (as of Symfony 2.8)
            $this->fallbackLocales = $this->translator->getFallbackLocales();
        } else {
            $this->fallbackLocales = false;
        }
    }

    /**
     * @return bool
     */
    protected function hasKnownFallbackLocales()
    {
        return $this->fallbackLocales !== false;
    }

    /**
     * Gets a message catalog for the specified or default locale.
     * Returns null only if the translator service doesn't implement the TranslatorBagInterface.
     *
     * @param string|null $locale
     * @return \Symfony\Component\Translation\MessageCatalogueInterface|null
     */
    protected function getCatalog($locale)
    {
        if (($this->translator) instanceof TranslatorBagInterface) {
            $catalog = $this->translator->getCatalogue($locale);
            if ($locale && !$catalog->all()) {
                throw new \LogicException("No messages in locale " . print_r($locale, true));
            }
            return $catalog;
        } else {
            return null;
        }
    }
}
