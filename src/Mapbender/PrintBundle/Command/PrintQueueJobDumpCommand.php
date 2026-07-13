<?php


namespace Mapbender\PrintBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

class PrintQueueJobDumpCommand extends AbstractPrintQueueCommand
{
    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Dump queued print job from the DB to JSON or YAML')
            ->addArgument('id', InputArgument::REQUIRED, 'Job ID to extract')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'json (default) or yaml', 'json')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);
        $validFormats = [
            'json',
            'yml',
            'yaml',
        ];
        $format = $input->getOption('format');
        if (!$format) {
            throw new \RuntimeException("Empty --format is not allowed");
        }
        $lcFormat = strtolower((string) $format);
        if (!in_array($lcFormat, $validFormats, true)) {
            $unsupportedMsg = 'Unsupported --format ' . print_r($format, true);
            $supportedMsg = 'Allowed values: ' . join(', ', $validFormats);
            throw new \RuntimeException("{$unsupportedMsg}; {$supportedMsg}");
        }
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $jobEntity = $this->repository->find($input->getArgument('id'));
        if (!$jobEntity) {
            throw new \RuntimeException("Job not found");
        }
        $jobData = $jobEntity->getPayload();

        match (strtolower((string) $input->getOption('format'))) {
            'json' => $output->writeln(json_encode($jobData, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_HEX_QUOT)),
            'yaml', 'yml' => $output->writeln(Yaml::dump($jobData, 9000)),
            // initialize should have already produced a (much better) message
            default => throw new \RuntimeException("Unsupported format"),
        };
        return 0;
    }
}
