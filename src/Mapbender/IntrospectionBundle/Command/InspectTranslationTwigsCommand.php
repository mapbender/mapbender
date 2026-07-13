<?php


namespace Mapbender\IntrospectionBundle\Command;


use Twig\Error\LoaderError;
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
    public function __construct(protected FilesystemLoader $twigLoader,
                                protected ElementInventoryService $inventory,
                                protected ElementEntityFactory $entityFactory,
                                protected ApplicationTemplateRegistry $templateRegistry)
    {
        parent::__construct(null);
    }

    public function configure(): void
    {
        $this
            ->addOption('elements', null, InputOption::VALUE_NONE)
            ->addOption('templates', null, InputOption::VALUE_NONE)
            ->addOption('admin', null, InputOption::VALUE_NONE)
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $targetOptions = [
            'elements',
            'templates',
            'admin',
        ];
        $targets = [];
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $resources = $this->collectResourcePaths($input);
        foreach ($resources as $resourceName) {
            try {
                $content = $this->twigLoader->getSourceContext($resourceName)->getCode();
            } catch (LoaderError) {
                // do absolutely nothing
                continue;
            }
            $this->inspectContent($output, $content, $resourceName);
        }
        return 0;
    }

    protected function inspectContent(OutputInterface $output, $content, $resourceName)
    {
        $stripped = preg_replace('#^\s*\{\s*#', '', (string) preg_replace('#\s*\}\s*$#', '', (string) $content));
        $rows = array_filter(preg_split('#\s*,\s*#', $stripped));
        if (!$rows) {
            $output->writeln("Empty resource: {$resourceName}");
        }
        foreach ($rows as $rowContent) {
            $matches = [];
            if (!preg_match('#^"(?<key>[^"]+)\"\s*:\s*"[{]{2}\s*[\\\'"](?<input>[^\\\'"]+)[\\\'"]\s*\|\s*trans\s*[}]{2}"$#', $rowContent, $matches)) {
                throw new \LogicException("Unidentifiable translation twig row content " . print_r($rowContent, true) . " in {$resourceName}");
            }
            if ($matches['key'] !== $matches['input']) {
                $output->writeln("Key <=> input mismatch: {$matches['key']} vs {$matches['input']} in {$resourceName}");
            }
        }
    }

    /**
     * @return mixed[]
     */
    protected function collectResourcePaths(InputInterface $input): array
    {
        $resources = [];
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

    /**
     * @return mixed[]
     */
    protected function collectElementResourcePaths(): array
    {
        $dummyApplication = new Application();
        $twigPaths = [];
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
        return $this->extractTemplateTranslationDependencies([
            new LoginTemplate(),
            new ManagerTemplate(),
        ]);
    }

    /**
     * @param TemplateAssetDependencyInterface[] $sources
     * @return string[]
     */
    protected function extractTemplateTranslationDependencies($sources): array
    {
        $rv = [];
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
    protected function filterTranslationDependencies(array $deps): array
    {
        $rv = [];
        foreach ($deps as $dep) {
            if (\preg_match('#\.twig$#', $dep)) {
                $rv[] = $dep;
            }
        }
        return $rv;
    }
}
