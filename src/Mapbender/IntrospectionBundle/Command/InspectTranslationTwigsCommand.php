<?php


namespace Mapbender\IntrospectionBundle\Command;


use Mapbender\Component\Application\TemplateAssetDependencyInterface;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\MapbenderBundle;
use Mapbender\CoreBundle\Component\Template;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Template\LoginTemplate;
use Mapbender\ManagerBundle\Template\ManagerTemplate;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InspectTranslationTwigsCommand extends ContainerAwareCommand
{
    public function configure()
    {
        $this
            ->setName('mapbender:inspect:translation:twigs')
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
        /** @var \Symfony\Bundle\TwigBundle\Loader\Filesystemloader $twigLoader */
        $twigLoader = $this->getContainer()->get('twig.loader');
        foreach ($resources as $resourceName) {
            try {
                $content = $twigLoader->getSourceContext($resourceName)->getCode();
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
        /** @var ElementInventoryService $service */
        $service = $this->getContainer()->get('mapbender.element_inventory.service');
        /** @var ElementFactory $factory  */
        $factory = $this->getContainer()->get('mapbender.element_factory.service');
        $classNames = $service->getRawInventory();
        foreach ($classNames as $className) {
            $element = $factory->newEntity($className, 'content', $dummyApplication);
            $componentDummy = $factory->componentFromEntity($element);
            $assetDependencies = $componentDummy->getAssets();
            if (!empty($assetDependencies['trans'])) {
                $twigPaths = array_merge($twigPaths, $this->filterTranslationDependencies($assetDependencies['trans']));
            }
        }
        return $twigPaths;
    }

    protected function collectTemplateResourcePaths()
    {
        $templateClasses = array();
        foreach ($this->getContainer()->getParameter('kernel.bundles') as $bundleClassName) {
            if (\is_a($bundleClassName, 'Mapbender\CoreBundle\Component\MapbenderBundle', true)) {
                /** @var MapbenderBundle $bundle */
                $bundle = new $bundleClassName();
                $templateClasses = array_merge($templateClasses, array_values($bundle->getTemplates()));
            }
        }
        $templateInstances = array();
        foreach ($templateClasses as $className) {
            /** @var Template|string $className */
            $templateInstances[] = new $className();
        }
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
