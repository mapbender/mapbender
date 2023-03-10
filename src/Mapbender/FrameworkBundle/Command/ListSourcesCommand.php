<?php


namespace Mapbender\FrameworkBundle\Command;


use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command mapender:sources:list
 *
 * Prints a list of all known map sources with some basic information.
 * Supports filtering by source type, title and url.
 */
class ListSourcesCommand extends Command
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        parent::__construct(null);
    }

    public static function getDefaultName()
    {
        return 'mapbender:sources:list';
    }

    protected function configure()
    {
        $this->setDescription('List all known sources, with optional filtering');
        $this->addOption('type', null, InputOption::VALUE_REQUIRED, 'Type of source ("WMS" or "WMTS")', null);
        $this->addOption('host-contains', null, InputOption::VALUE_REQUIRED, '(Portion of) host name', null);
        $this->addOption('url-contains', null, InputOption::VALUE_REQUIRED, '(Portion of) full url', null);
        $this->addOption('title-contains', null, InputOption::VALUE_REQUIRED, '(Portion of) source title', null);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->managerRegistry->getManagerForClass(Source::class);
        /** @var Source[] $sources */
        $sources = $em->getRepository(Source::class)->findAll();
        foreach ($sources as $source) {
            if ($this->checkFilters($source, $input)) {
                $this->showSource($output, $source, $input);
            }
        }
    }

    protected function checkFilters(Source $source, InputInterface $input)
    {
        if ($input->getOption('type')) {
            if (\strtolower($source->getType()) !== \strtolower($input->getOption('type'))) {
                return false;
            }
        }
        if ($input->getOption('host-contains')) {
            $parts = \parse_url($source->getOriginUrl());
            if (false === \stripos($parts['host'], $input->getOption('host-contains'))) {
                return false;
            }
        }
        if ($input->getOption('url-contains')) {
            if (false === \stripos($source->getOriginUrl(), $input->getOption('url-contains'))) {
                return false;
            }
        }
        if ($input->getOption('title-contains')) {
            if (false === \mb_stripos($source->getTitle(), $input->getOption('title-contains'))) {
                return false;
            }
        }
        return true;
    }

    protected function showSource(OutputInterface $output, Source $source, InputInterface $input)
    {
        if ($output->getVerbosity() <= OutputInterface::VERBOSITY_QUIET) {
            $output->writeln($source->getId(), $output->getVerbosity());
        } else {
            $output->writeln('#' . $source->getId() . ' ' . $source->getType() . ' ' . $source->getTitle());
            $output->writeln('  ' . $source->getOriginUrl());
        }
    }
}
