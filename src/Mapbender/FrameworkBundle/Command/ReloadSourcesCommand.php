<?php


namespace Mapbender\FrameworkBundle\Command;


use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
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
        $loader = $this->sourceTypeDirectory->getSourceLoaderByType($source->getType());
        $loader->refresh($source, $source);
    }

    protected function getEntityManager()
    {
        return $this->managerRegistry->getManagerForClass(Source::class);
    }
}
