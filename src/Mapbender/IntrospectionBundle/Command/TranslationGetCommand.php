<?php


namespace Mapbender\IntrospectionBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Prints translated messages to the command line.
 * Scripting hint: Use -q to reduce output to translated message only.
 *
 * Scripting hint 2: batch translate a key from all currently supported locales:
 * echo -n 'de en es fr it nl pt ru tr'| xargs -n1 -d\  -- bash -c 'echo -n $0:\ ; app/console translation:get -q --domain=messages mb.core.simplesearch.error.geometry.missing --locale=$0'
 */
class TranslationGetCommand extends AbstractTranslationCommand
{
    protected function configure()
    {
        $this
            ->setName('translation:get')
            ->addArgument('input', InputArgument::REQUIRED, 'Translation input')
            ->addOption('locale', null, InputOption::VALUE_REQUIRED)
            ->addOption('domain', null, InputOption::VALUE_REQUIRED)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $locale = $input->getOption('locale') ?: null;
        $catalog = $this->getCatalog($locale);
        if ($catalog && $locale) {
            if (!$catalog->all()) {
                throw new \InvalidArgumentException("No messages in locale " . print_r($locale, true));
            }
        }

        $domain = $input->getOption('domain') ?: null;
        $translatorInput = $input->getArgument('input');
        $translated = $this->translator->trans($translatorInput, array(), $domain, $locale);
        $localeHit = $catalog && $input != $translatorInput && $catalog->defines($translatorInput, $domain);
        if ($localeHit) {
            $displayLocale = "locale catalog " . ($locale ?: $this->translator->getLocale());
        } elseif ($this->hasKnownFallbackLocales()) {
            $displayLocale = "fallback locale catalog(s) " . implode(', ', $this->fallbackLocales);
        } else {
            $displayLocale = "undetectable locale catalog";
        }
        $output->writeln("Message from {$displayLocale}:", OutputInterface::VERBOSITY_NORMAL);
        $output->writeln($translated, OutputInterface::VERBOSITY_QUIET);

        // If translation didn't do anything, return exit code 1 to signal something isn't right to
        // invoking scripts
        return ($translated == $translatorInput) ? 1 : 0;
    }
}
