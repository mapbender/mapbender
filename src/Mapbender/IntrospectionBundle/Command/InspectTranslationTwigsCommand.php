<?php


namespace Mapbender\IntrospectionBundle\Command;


use Mapbender\Component\Application\TemplateAssetDependencyInterface;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\FrameworkBundle\Component\ApplicationTemplateRegistry;
use Mapbender\FrameworkBundle\Component\ElementEntityFactory;
use Mapbender\ManagerBundle\Template\LoginTemplate;
use Mapbender\ManagerBundle\Template\ManagerTemplate;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Loader\FilesystemLoader;

class InspectTranslationTwigsCommand extends Command
{
    /** @var FilesystemLoader */
    protected $twigLoader;
    /** @var ElementInventoryService */
    protected $inventory;
    /** @var ElementEntityFactory */
    protected $entityFactory;
    /** @var ApplicationTemplateRegistry */
    protected $templateRegistry;

    public function __construct(FilesystemLoader $twigLoader,
                                ElementInventoryService $inventory,
                                ElementEntityFactory $entityFactory,
                                ApplicationTemplateRegistry $templateRegistry)
    {
        $this->twigLoader = $twigLoader;
        $this->inventory = $inventory;
        $this->entityFactory = $entityFactory;
        $this->templateRegistry = $templateRegistry;
        parent::__construct(null);
    }

    public function configure()
    {
        $this
            ->addOption('elements', null, InputOption::VALUE_NONE)
            ->addOption('templates', null, InputOption::VALUE_NONE)
            ->addOption('admin', null, InputOption::VALUE_NONE)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $targetOptions = array(
            'elements',
            'templates',
            'admin',
        );
        $targets = array();
        foreach ($targetOptions as $optionName) {
            if ($input->getOption($optionName)) {
                $targets[] = $optionName;
            }
        }
        if (!$targets) {
            foreach ($targetOptions as $optionName) {
                $input->setOption($optionName, true);
            }
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $resources = $this->collectResourcePaths($input);
        foreach ($resources as $resourceName) {
            try {
                $content = $this->twigLoader->getSourceContext($resourceName)->getCode();
            } catch (\Twig\Error\LoaderError $e) {
                // do absolutely nothing
                continue;
            }
            $this->inspectContent($output, $content, $resourceName);
        }
    }

    protected function inspectContent(OutputInterface $output, $content, $resourceName)
    {
        $stripped = preg_replace('#^\s*\{\s*#', '', preg_replace('#\s*\}\s*$#', '', $content));
        $rows = array_filter(preg_split('#\s*,\s*#', $stripped));
        if (!$rows) {
            $output->writeln("Empty resource: {$resourceName}");
        }
        foreach ($rows as $rowContent) {
            $matches = array();
            if (!preg_match('#^"(?<key>[^"]+)\"\s*:\s*"[{]{2}\s*[\\\'"](?<input>[^\\\'"]+)[\\\'"]\s*\|\s*trans\s*[}]{2}"$#', $rowContent, $matches)) {
                throw new \LogicException("Unidentifiable translation twig row content " . print_r($rowContent, true) . " in {$resourceName}");
            }
            if ($matches['key'] !== $matches['input']) {
                $output->writeln("Key <=> input mismatch: {$matches['key']} vs {$matches['input']} in {$resourceName}");
            }
        }
    }

    protected function collectResourcePaths(InputInterface $input)
    {
        $resources = array();
        if ($input->getOption('elements')) {
            $resources = array_merge($resources, $this->collectElementResourcePaths());
        }
        if ($input->getOption('templates')) {
            $resources = array_merge($resources, $this->collectTemplateResourcePaths());
        }
        if ($input->getOption('admin')) {
            $resources = array_merge($resources, $this->collectAdminResourcePaths());
        }
        return $resources;
    }

    protected function collectElementResourcePaths()
    {
        $dummyApplication = new Application();
        $twigPaths = array();
        $classNames = $this->inventory->getRawInventory();
        foreach ($classNames as $className) {
            $element = $this->entityFactory->newEntity($className, 'content', $dummyApplication);
            $handler = $this->inventory->getFrontendHandler($element);
            $assetDependencies = $handler->getRequiredAssets($element);
            if (!empty($assetDependencies['trans'])) {
                $twigPaths = array_merge($twigPaths, $this->filterTranslationDependencies($assetDependencies['trans']));
            }
        }
        return $twigPaths;
    }

    protected function collectTemplateResourcePaths()
    {
        $templateInstances = $this->templateRegistry->getAll();
        return $this->extractTemplateTranslationDependencies($templateInstances);
    }

    protected function collectAdminResourcePaths()
    {
        return $this->extractTemplateTranslationDependencies(array(
            new LoginTemplate(),
            new ManagerTemplate(),
        ));
    }

    /**
     * @param TemplateAssetDependencyInterface[] $sources
     * @return string[]
     */
    protected function extractTemplateTranslationDependencies($sources)
    {
        $rv = array();
        foreach ($sources as $source) {
            /** @var TemplateAssetDependencyInterface $source */
            $rv = array_merge($rv, $this->filterTranslationDependencies($source->getAssets('trans')));
            $rv = array_merge($rv, $this->filterTranslationDependencies($source->getLateAssets('trans')));
        }
        return $rv;
    }

    /**
     * @param string[] $deps
     * @return string[]
     */
    protected function filterTranslationDependencies(array $deps)
    {
        $rv = array();
        foreach ($deps as $dep) {
            if (\preg_match('#\.twig$#', $dep)) {
                $rv[] = $dep;
            }
        }
        return $rv;
    }
}
