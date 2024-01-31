<?php

namespace Mapbender\CoreBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Translation\Exception\InvalidResourceException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class TranslationCommand extends Command
{
    protected static $defaultName = 'mapbender:normalize-translations';

    public function __construct(private KernelInterface $kernel)
    {
        parent::__construct();
    }

    const ARGUMENT_LANGUAGE = "language";
    const OPTION_SOURCE_LANGUAGE = "source-language";
    const OPTION_PRINT_MISSING_KEYS = "print-missing-keys";
    const OPTION_ADD_MISSING_KEYS = "add-missing-keys";
    const OPTION_MISSING_KEY_PREFIX = "missing-key-prefix";

    private InputInterface $input;
    private OutputInterface $output;

    private int $keysMissingCounter = 0;


    protected function configure(): void
    {
        $this
            ->setDescription('Normalize YAML order of translation files and mark missing translations.')
            ->setHelp('This command normalizes YAML order of translation files and adds missing translations.')
            ->addArgument(self::ARGUMENT_LANGUAGE, mode: InputArgument::REQUIRED, description: 'the language code (e.g. de) that should be processed')
            ->addOption(self::OPTION_SOURCE_LANGUAGE, mode: InputOption::VALUE_OPTIONAL, description: 'the source language (e.g. en) that is seen as default. It is assumed all keys are present in the source language', default: 'en')
            ->addOption(self::OPTION_ADD_MISSING_KEYS, 'a', description: 'if set to true, entries not present in the target language will be added (marked with a prefix)')
            ->addOption(self::OPTION_MISSING_KEY_PREFIX, mode: InputOption::VALUE_OPTIONAL, description: 'the prefix to mark keys that were automatically added and are yet to translate', default: 'TRANSLATE: ')
            ->addOption(self::OPTION_PRINT_MISSING_KEYS, 'p', description: 'if set to true, entries not present in the target language will be logged to the console.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;

        $sourceLanguage = $input->getOption(self::OPTION_SOURCE_LANGUAGE);
        $targetLanguage = $input->getArgument(self::ARGUMENT_LANGUAGE);

        foreach ($this->kernel->getBundles() as $bundle) {
            if (!str_starts_with($bundle->getNamespace(), 'Mapbender') && !str_starts_with($bundle->getNamespace(), 'FOM')) continue;

            $translationFolder = $bundle->getPath() . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR . 'translations';
            if (!is_dir($translationFolder)) continue;
            $sourceFile = $this->getTranslationFile($translationFolder, $sourceLanguage);
            if ($sourceFile === null) {
                $output->writeln("No translation file found for bundle " . $bundle->getNamespace() . " and source language " . $sourceLanguage . "; skipping");
                continue;
            }

            $targetFile = $this->getTranslationFile($translationFolder, $targetLanguage);
            if ($targetFile === null) {
                $output->writeln("No translation file found for bundle " . $bundle->getNamespace() . " and target language " . $targetLanguage . "; skipping");
                continue;
            }

            $output->writeln("Processing translation for " . $bundle->getNamespace() . "  …");
            $this->normalizeYamlOrder($sourceFile, $targetFile);
            $successMessage = 'Translations keys normalized for bundle ' . $bundle->getNamespace() . '. ';
            $successMessage .= match ($this->keysMissingCounter) {
                0 => "No keys were",
                1 => "1 key was",
                default => $this->keysMissingCounter . " keys were"
            };
            $successMessage .= $input->getOption(self::OPTION_ADD_MISSING_KEYS) ? " added." : " missing.";
            $output->writeln($successMessage . "\n");
            $this->keysMissingCounter = 0;
        }
        return Command::SUCCESS;
    }

    private function getTranslationFile(string $translationFolder, mixed $sourceLanguage): string|null
    {
        $sourceFile = $translationFolder . DIRECTORY_SEPARATOR . "messages+intl-icu." . $sourceLanguage . ".yaml";
        if (!is_file($sourceFile)) {
            $sourceFile = $translationFolder . DIRECTORY_SEPARATOR . "messages." . $sourceLanguage . ".yaml";
        }
        if (!is_file($sourceFile)) {
            $sourceFile = $translationFolder . DIRECTORY_SEPARATOR . "messages+intl-icu." . $sourceLanguage . ".yml";
        }
        if (!is_file($sourceFile)) {
            $sourceFile = $translationFolder . DIRECTORY_SEPARATOR . "messages." . $sourceLanguage . ".yml";
        }
        return is_file($sourceFile) ? $sourceFile : null;
    }

    private function normalizeYamlOrder(string $sourceFile, string $targetFile): void
    {
        $messagesSource = $this->parseYamlFile($sourceFile);
        $messagesTarget = $this->parseYamlFile($targetFile);

        $newMessages = $this->recursiveNormalizeOrder($messagesSource, $messagesTarget);

        $this->writeYamlFile($sourceFile, $messagesSource);
        $this->writeYamlFile($targetFile, $newMessages);
    }

    private function recursiveNormalizeOrder(array $source, array $target, array $path = []): array
    {
        $newTarget = [];
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $targetValue = array_key_exists($key, $target) ? $target[$key] : [];
                $newTarget[$key] = $this->recursiveNormalizeOrder($value, $targetValue, [...$path, $key]);
                if (!empty($target[$key])) unset($target[$key]);
            } elseif (array_key_exists($key, $target)) {
                $newTarget[$key] = $target[$key];
                unset($target[$key]);
            } else {
                $this->keysMissingCounter++;

                if ($this->input->getOption(self::OPTION_ADD_MISSING_KEYS)) {
                    $newTarget[$key] = $this->input->getOption(self::OPTION_MISSING_KEY_PREFIX) . $value;
                } elseif ($this->input->getOption(self::OPTION_PRINT_MISSING_KEYS)) {
                    $fullKeyName = implode('.', $path) . (empty($path) ? '' : '.') . $key;
                    $this->output->writeln('<comment>Translation key "' . $fullKeyName . '" is missing in target translation file</comment>');
                }
            }
        }
        foreach ($target as $key => $value) {
            $fullKeyName = implode('.', $path) . (empty($path) ? '' : '.') . $key;
            $this->output->writeln('<comment>Found unexpected key "' . $fullKeyName . '" in translated file not present in original</comment>');
            $newTarget[$key] = $value;
        }
        return $newTarget;
    }

    private function parseYamlFile(string $filePath): array
    {
        try {
            $messages = Yaml::parse(file_get_contents($filePath), Yaml::PARSE_DATETIME);
        } catch (ParseException $e) {
            throw new InvalidResourceException(sprintf('The file "%s" does not contain valid YAML: ', $filePath) . $e->getMessage(), 0, $e);
        }

        if (null !== $messages && !\is_array($messages)) {
            throw new InvalidResourceException(sprintf('Unable to load file "%s".', $filePath));
        }

        return $messages ?: [];
    }

    private function writeYamlFile(string $filePath, array $contents): void
    {
        $yamlFlags = Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK;
        file_put_contents($filePath, Yaml::dump($contents, 10, indent: 2, flags: $yamlFlags));
    }


}
