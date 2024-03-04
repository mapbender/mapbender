<?php


namespace Mapbender\WmsBundle\Command;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SourceShowCommand extends AbstractSourceCommand
{

    protected function configure()
    {
        $this
            ->setName('mapbender:wms:show')
            ->setDescription('Displays layer information of a persisted WMS source')
            ->addArgument('id', InputArgument::OPTIONAL, 'Id or url of the source. If omitted, all sources are shown')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $idOrUrl = $input->getArgument('id');
        if (!$idOrUrl) {
            return $this->showAllSources($output);
        }

        if (is_numeric($idOrUrl)) {
            return $this->showSourceById($idOrUrl, $output);
        }

        return $this->showSourceByUrl($idOrUrl, $input, $output);
    }

    private function showSourceByUrl(string $url, InputInterface $input, OutputInterface $output): int
    {
        $source = $this->getEntityManager()->getRepository(WmsSource::class)->findOneBy(['originUrl' => $url]);
        if ($source === null) {
            $io = new SymfonyStyle($input, $output);
            $io->error("Could not find a wms with origin url $url");
            return Command::FAILURE;
        }
        $this->showSource($output, $source);
        return Command::SUCCESS;
    }

    private function showSourceById(int $id, OutputInterface $output): int
    {
        $source = $this->getSourceById($id);
        $this->showSource($output, $source);
        return Command::SUCCESS;
    }

    private function showAllSources(OutputInterface $output): int
    {
        $sources = $this->getEntityManager()->getRepository(Source::class)->findAll();
        foreach ($sources as $source) {
            $this->showSource($output, $source);
            $output->writeln('');
        }
        return Command::SUCCESS;
    }
}
