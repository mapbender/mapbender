<?php


namespace Mapbender\FrameworkBundle\Command;


use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\SourceInstanceUpdateOptions;
use Mapbender\Component\SourceLoader;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command mapbender:sources:reload
 *
 * Unlike mapbender:wms:reload:...
 * * Reloads any source (WMS, WMTS, possible future extensions)
 * * Does not require knowledge of URL, only id
 * * Supports bulk processing (can pass multiple ids)
 */
class ReloadSourcesCommand extends Command
{
    /** @var ManagerRegistry */
    protected $managerRegistry;
    /** @var TypeDirectoryService */
    protected $sourceTypeDirectory;

    public function __construct(ManagerRegistry $managerRegistry,
                                TypeDirectoryService $sourceTypeDirectory)
    {
        $this->managerRegistry = $managerRegistry;
        $this->sourceTypeDirectory = $sourceTypeDirectory;
        parent::__construct();
    }

    public static function getDefaultName()
    {
        return 'mapbender:sources:reload';
    }

    protected function configure()
    {
        $this
            ->setHelp('Reloads map sources')
            ->addArgument('ids', InputArgument::REQUIRED | InputArgument::IS_ARRAY)
            ->addOption('new-layers-active', null, InputOption::VALUE_REQUIRED, 'Set newly found layers active in instances (0 or 1)')
            ->addOption('new-layers-selected', null, InputOption::VALUE_REQUIRED, 'Set newly found layers selected in instances (0 or 1)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $unrenderedException = null;
        foreach ($input->getArgument('ids') as $id) {
            try {
                $this->processIdArgument($id, $input, $output);
            } catch (\Exception|\Throwable $e) {
                if ($unrenderedException) {
                    $this->getApplication()->renderThrowable($unrenderedException, $output);
                }
                $unrenderedException = $e;
            }
        }
        if ($unrenderedException) {
            throw $unrenderedException;
        }
    }

    protected function processIdArgument($id, InputInterface $input, OutputInterface $output)
    {
        /** @var Source|null $source */
        $source = $this->getEntityManager()->find(Source::class, $id);
        if (!$source) {
            throw new \LogicException("No source with id " . \var_export($id, true));
        }
        $output->writeln('Relading ' . \implode(' ', array(
                $source->getTypeLabel(),
                '#' . $source->getId(),
                '"' . $source->getTitle() . '"',
        )));
        $this->processSource($source, $input, $output);
    }

    protected function processSource(Source $source, InputInterface $input, OutputInterface $output)
    {
        $em = $this->getEntityManager();
        $em->beginTransaction();
        try {
            $loader = $this->getReloadHandler($source);
            $instanceUpdateOptions = $this->getInstanceUpdateOptions($source, $input);
            $loader->refresh($source, $source, $instanceUpdateOptions);
            $em->persist($source);
            $em->flush();
            $em->commit();
        } catch (\Throwable|\Exception $e) {
            $em->rollback();
            throw $e;
        }
    }

    protected function getInstanceUpdateOptions(Source $source, InputInterface $input): SourceInstanceUpdateOptions
    {
        $options = $this->getReloadHandler($source)->getDefaultInstanceUpdateOptions($source);
        if (null !== $input->getOption('new-layers-active')) {
            $options->newLayersActive = $this->parseBoolean($input->getOption('new-layers-active'));
        }
        if (null !== $input->getOption('new-layers-selected')) {
            $options->newLayersSelected = $this->parseBoolean($input->getOption('new-layers-selected'));
        }
        return $options;
    }

    protected function parseBoolean(string $x = null): bool
    {
        if (\strlen($x) && !\is_numeric($x)) {
            // Allow (abbreviations of) "true", "yes"
            return \in_array(\strtolower($x)[0], array(
                'y',
                't',
            ));
        } else {
            // "0" => false, "1" => true
            return !!$x;
        }
    }

    protected function getReloadHandler(Source $source): SourceLoader
    {
        return $this->sourceTypeDirectory->getSourceLoaderByType($source->getType());
    }

    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(Source::class);
    }
}
