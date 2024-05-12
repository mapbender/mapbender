<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SourceShowCommand extends AbstractSourceCommand
{

    protected static $defaultName = 'mapbender:wms:show';
    protected function configure(): void
    {
        $this
            ->setDescription('Displays layer information of a persisted WMS source')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id or url of the source. If omitted, all sources are shown')
            ->addOption('json', null, InputOption::VALUE_NONE, 'if set, output is formatted as json')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $idOrUrl = $input->getArgument('id');
        if (!$idOrUrl) {
            return $this->showAllSources($input, $output);
        }

        if (is_numeric($idOrUrl)) {
            return $this->showSourceById($idOrUrl, $input, $output);
        }

        return $this->showSourceByUrl($idOrUrl, $input, $output);
    }

    private function showSourceByUrl(string $url, InputInterface $input, OutputInterface $output): int
    {
        $json = $input->getOption('json');
        $sources = $this->getEntityManager()->getRepository(WmsSource::class)->findBy(['originUrl' => $url]);
        if (empty($sources) && !$json) {
            $io = new SymfonyStyle($input, $output);
            $io->error("Could not find a wms with origin url $url");
            return Command::FAILURE;
        }

        if ($json) {
            $output->writeln(json_encode(array_map(function($source) {
                return $this->getSourceDetails($source);
            }, $sources), JSON_PRETTY_PRINT));
        } else {
            foreach ($sources as $source) {
                $this->showSource($output, $source);
                $output->writeln('');
            }
        }
        return Command::SUCCESS;
    }

    private function showSourceById(int $id, InputInterface $input, OutputInterface $output): int
    {
        try {
            $source = $this->getSourceById($id);
        } catch (\LogicException $e) {
            $io = new SymfonyStyle($input, $output);
            $io->error("Could not find a wms with id $id");
            return Command::FAILURE;
        }

        $json = $input->getOption('json');

        if ($json) {
            $output->writeln(json_encode($this->getSourceDetails($source), JSON_PRETTY_PRINT));
        } else {
            $this->showSource($output, $source);
        }
        return Command::SUCCESS;
    }

    private function showAllSources(InputInterface $input, OutputInterface $output): int
    {
        $json = $input->getOption('json');
        $sources = $this->getEntityManager()->getRepository(Source::class)->findAll();

        if ($json) {
            $output->writeln(json_encode(array_map(function ($source) {
                return $this->getSourceDetails($source);
            }, $sources), JSON_PRETTY_PRINT));
        } else {
            foreach ($sources as $source) {
                $this->showSource($output, $source, $json);
                $output->writeln('');
            }
        }

        return Command::SUCCESS;
    }
}
