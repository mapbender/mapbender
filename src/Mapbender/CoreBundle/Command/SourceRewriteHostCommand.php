<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Transformer\BaseUrlTransformer;
use Mapbender\Component\Transformer\ChangeTrackingTransformer;
use Mapbender\Component\Transformer\OneWayTransformer;
use Mapbender\Component\Transformer\Target\MutableUrlTarget;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SourceRewriteHostCommand extends Command
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        parent::__construct(null);
    }

    protected function configure()
    {
        $this->setDescription("Update host names in source urls, without reloading capabilities");
        $this->addArgument('from', InputArgument::REQUIRED);
        $this->addArgument('to', InputArgument::REQUIRED);
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, "Preview changes without saving");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $transformer = $this->getTransformer($input);
        $this->processSources($input, $output, $transformer);
    }

    protected function getTransformer(InputInterface $input)
    {
        $from = $input->getArgument('from');
        $to = $input->getArgument('to');
        if (!$from || !$to) {
            throw new \InvalidArgumentException("Empty strings not allowed");
        }
        return new BaseUrlTransformer($from, $to, true);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param OneWayTransformer $transformer
     */
    protected function processSources(InputInterface $input, OutputInterface $output,
                                     OneWayTransformer $transformer)
    {
        $em = $this->getEntityManager();
        $repository = $em->getRepository('MapbenderCoreBundle:Source');
        /** @var Source[] $sources */
        $sources = $repository->findAll();

        $totalUrlsChanged = 0;
        $totalUrlsUnchanged = 0;
        $sourcesChanged = 0;
        $sourcesUnchanged = 0;

        if ($input->getOption('dry-run')) {
            $changeVerbosities = array(
                'heading' => OutputInterface::VERBOSITY_QUIET,
                'url' => OutputInterface::VERBOSITY_QUIET,
            );
        } else {
            $changeVerbosities = array(
                'heading' => OutputInterface::VERBOSITY_NORMAL,
                'url' => OutputInterface::VERBOSITY_VERBOSE,
            );
        }

        foreach ($sources as $source) {
            $sourceDescriptor = "{$source->getType()} source #{$source->getId()} / {$source->getTitle()}";
            $output->writeln("Processing {$sourceDescriptor}", OutputInterface::VERBOSITY_DEBUG);
            $trackingWrapper = new ChangeTrackingTransformer($transformer, true);
            if ($source instanceof MutableUrlTarget) {
                $source->mutateUrls($trackingWrapper);
                $changes = $trackingWrapper->getChanges();
                $unchanged = $trackingWrapper->getUnchanged();
                if ($changes) {
                    $output->writeln(count($changes) . " modified urls in {$sourceDescriptor}", $changeVerbosities['heading']);
                    foreach ($changes as $change) {
                        $output->writeln("  from {$change->getBefore()} (x{$change->getOccurrences()})", $changeVerbosities['url']);
                        $output->writeln("  to   {$change->getAfter()}", $changeVerbosities['url']);
                    }
                    ++$sourcesChanged;
                    if (!$input->getOption('dry-run')) {
                        $em->persist($source);
                    }
                } else {
                    ++$sourcesUnchanged;
                }
                if ($unchanged) {
                    $output->writeln("* " . count($unchanged) . " unmodified urls in {$sourceDescriptor}:", OutputInterface::VERBOSITY_VERY_VERBOSE);
                    foreach ($unchanged as $nonchange) {
                        $output->writeln("** {$nonchange->getBefore()} (x{$nonchange->getOccurrences()})", OutputInterface::VERBOSITY_DEBUG);
                    }
                }
                $totalUrlsChanged += count($changes);
                $totalUrlsUnchanged += count($unchanged);
            }
        }
        if (!$input->getOption('dry-run')) {
            $em->flush();
        }
        $output->writeln("Summary:");
        $output->writeln($sourcesChanged . " sources changed");
        $output->writeln($totalUrlsChanged . " urls changed");
        $output->writeln($sourcesUnchanged . " sources unchanged");
        $output->writeln($totalUrlsUnchanged . " urls unchanged");
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->managerRegistry->getManager();
        return $em;
    }
}
