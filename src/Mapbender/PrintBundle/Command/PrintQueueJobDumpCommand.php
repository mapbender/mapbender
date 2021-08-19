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
    protected function configure()
    {
        $this
            ->setDescription('Dump queued print job from the DB to JSON or YAML')
            ->addArgument('id', InputArgument::REQUIRED, 'Job ID to extract')
            ->addOption('format', null, InputOption::VALUE_REQUIRED, 'json (default) or yml', 'json')
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
        $validFormats = array(
            'json',
            'yml',
            'yaml',
        );
        $format = $input->getOption('format');
        if (!$format) {
            throw new \RuntimeException("Empty --format is not allowed");
        }
        $lcFormat = strtolower($format);
        if (!in_array($lcFormat, $validFormats, true)) {
            $unsupportedMsg = 'Unsupported --format ' . print_r($format, true);
            $supportedMsg = 'Allowed values: ' . join(', ', $validFormats);
            throw new \RuntimeException("{$unsupportedMsg}; {$supportedMsg}");
        }
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobEntity = $this->repository->find($input->getArgument('id'));
        if (!$jobEntity) {
            throw new \RuntimeException("Job not found");
        }
        $jobData = $jobEntity->getPayload();

        switch (strtolower($input->getOption('format'))) {
            case 'json':
                $output->writeln(json_encode($jobData, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_HEX_QUOT));
                break;
            case 'yaml':
            case 'yml':
                $output->writeln(Yaml::dump($jobData, 9000));
                break;
            default:
                // initialize should have already produced a (much better) message
                throw new \RuntimeException("Unsupported format");
        }
    }
}
