<?php


namespace Mapbender\IntrospectionBundle\Command;


use Mapbender\Component\ClassUtil;
use Mapbender\Component\Element\ElementServiceInterface;
use Mapbender\CoreBundle\Component\ElementInterface;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\Component\BundleUtil;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\ElementFormFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * CLI command to inspect Mapbender element classes and perform some sanity checks.
 *
 * Will detect / specially highlight:
 * * missing / empty frontend template file (based on automatic template path calulation!)
 * * reimplemented render / getType / getFormTemplate methods
 * * missing AdminType class
 * * Template not in same bundle as Element (for both frontend + admin template)
 * * Element inheriting from other concrete Element
 * * (overridden) AdminType class != automatically calculated AdminType class (to detect inheritance issues)
 * * (overridden) form template != automatically calculated form template (to detect inheritance / convention issues)
 *
 */
class ElementClassesCommand extends Command
{
    /** @var KernelInterface */
    protected $kernel;
    /** @var ElementFormFactory */
    protected $elementFormFactory;
    /** @var ElementInventoryService */
    protected $inventory;

    public function __construct(KernelInterface $kernel,
                                ElementInventoryService $inventory,
                                ElementFormFactory $elementFormFactory)
    {
        $this->kernel = $kernel;
        $this->inventory = $inventory;
        $this->elementFormFactory = $elementFormFactory;

        parent::__construct(null);
    }

    protected function configure()
    {
        $this->setHelp('Summarizes information about all available Mapbender Element classes in all currently active bundles');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $noteStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('note', $noteStyle);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $elementNames = $this->inventory->getActiveInventory();
        $headers = array(
            'Name',
            'Comments',
            'Widget Constructor',
            'AdminType',
            'AdminTemplate',
            'Implicit asset references',
        );

        $rows = array();
        $application = new Application();
        foreach ($elementNames as $elementName) {
            try {
                $entity = new Element();
                $entity->setConfiguration(array());
                $entity->setClass($elementName);
                $entity->setApplication($application);
                $handler = $this->inventory->getFrontendHandler($entity);
                $rows[$elementName] = $this->formatElementInfo($entity, $handler);
            } catch (\Exception $e) {
                $rows[$elementName] = array(
                    "<error>$elementName</error>",
                    "<error>{$e->getMessage()}</error>",
                );
            }
        }
        $this->renderInfoPerNamespace($input, $output, $headers, $rows);
    }

    protected function renderInfoPerNamespace(InputInterface $input, OutputInterface $output, $headers, $rows)
    {
        ksort($rows);
        // split the information into buckets by bundle namespace
        $namespaceBuckets = array();
        foreach ($rows as $elementName => $cells) {
            $classNameParts = explode('\\', $elementName);
            $bundleNamespace = implode('\\', array_slice($classNameParts, 0, 2));
            if (!array_key_exists($bundleNamespace, $namespaceBuckets)) {
                $namespaceBuckets[$bundleNamespace] = array();
            }
            $tail = array_slice($classNameParts, 2);
            // Highlight classes that are not immediately in namespace "<bundle>\Element\".
            // Otherwise, omit the "Element" part of the namespace for a more compact list
            if ($tail[0] != 'Element') {
                $shortenedClassName = "<comment>{$tail[0]}</comment>" . implode('\\', $tail);
            } elseif (count($tail) != 2) {
                $shortenedClassName = "<comment>" . implode('\\', $tail) . "</comment>";
            } else {
                $shortenedClassName = $tail[1];
            }
            $cells[0] = str_replace($elementName, $shortenedClassName, $cells[0]);
            $namespaceBuckets[$bundleNamespace][$elementName] = $cells;
        }
        ksort($namespaceBuckets);

        foreach ($namespaceBuckets as $bundleNamespace => $rows) {
            $output->writeln("Elements in $bundleNamespace:");
            $this->renderTable($input, $output, $headers, $rows);
        }
    }

    /**
     * @param Element $element
     * @param ElementServiceInterface $handler
     * @return string[]
     * @throws \ReflectionException
     */
    protected function formatElementInfo(Element $element, ElementServiceInterface $handler)
    {
        $cells = array(
            $element->getClass(),
            $this->formatElementComments($element),
            $this->formatGetWidgetName($handler, $element),
            $this->formatAdminType($element),
            $this->formatAdminTemplateInfo($element),
            $this->formatAssetRefStatus($handler, $element),
        );
        return $cells;
    }

    /**
     * @param ElementServiceInterface $handler
     * @param Element $element
     * @return string
     */
    protected static function formatGetWidgetName(ElementServiceInterface $handler, Element $element)
    {
        try {
            $widgetConstructor = $handler->getWidgetName($element);
            $rc = new \ReflectionClass($element->getClass());
            $rm = $rc->getMethod('getWidgetName');
        } catch (\ReflectionException $e) {
            return '<error>No reflection</error>';
        }
        if (!$widgetConstructor) {
            return '<error>No widget constructor</error>';
        }

        if ($rm->class !== $rc->getName()) {
            if ($rm->getDeclaringClass()->isAbstract()) {
                $comment = "<error>from abstract {$rm->getDeclaringClass()->getName()}</error>";
            } else {
                $comment = "<info>from {$rm->getDeclaringClass()->getName()}</info>";
            }
        } else {
            $comment = "";
        }
        if (!trim($widgetConstructor)) {
            $widgetConstructor = '<error>NONE!</error>';
        }
        return trim("{$widgetConstructor}\n{$comment}");
    }

    /**
     * @param Element $element
     * @return string
     */
    protected function formatAdminType($element)
    {
        try {
            $rc = new \ReflectionClass($element->getClass());
            $rm = $rc->getMethod('getType');
        } catch (\ReflectionException $e) {
            return "<error>No reflection</error>";
        }

        $adminType = $this->elementFormFactory->getConfigurationFormType($element);
        if (!$adminType) {
            return '<comment>none</comment>';
        }
        $elementBNS = BundleUtil::extractBundleNamespace($element->getClass());
        try {
            $adminTypeBNS = BundleUtil::extractBundleNamespace($adminType);
            if (!ClassUtil::exists($adminType)) {
                // mark missing class as error
                return "<error>{$adminType}</error>";
            } else {
                $formatted = "{$adminType}";
            }
        } catch (\RuntimeException $e) {
            // assume servicy admin type
            if (false === strpos($adminType, '\\')) {
                $formatted = "service <info>{$adminType}</info>";
                $adminTypeBNS = null;
            } else {
                throw $e;
            }
        }
        if ($adminTypeBNS && $adminTypeBNS != $elementBNS) {
            $formatted = str_replace($adminTypeBNS, "<comment>{$adminTypeBNS}</comment>", $formatted);
        }
        if ($rm->class != $rc->getName()) {
            $formatted .= "\n<comment>(inherited from</comment>\n";
            if ($rm->getDeclaringClass()->isAbstract()) {
                $formatted .= "<error>{$rm->class})</error>";
            } else {
                $formatted .= "<comment>{$rm->class})</comment>";
            }
        }
        return $formatted;
    }

    /**
     * @param ElementServiceInterface $handler
     * @param Element $element
     * @return string
     */
    protected function formatAssetRefStatus(ElementServiceInterface $handler, Element $element)
    {
        $explicitRefPattern = '^(/|\.\./|(@[\w]+Bundle/)|([\w]+Bundle:))';
        $assetRefs = $handler->getRequiredAssets($element) ?: array(array());   // for array_merge safety with empty input
        $implicitRefs = array();
        foreach (call_user_func_array('array_merge', $assetRefs) as $ref) {
            if (!preg_match("#{$explicitRefPattern}#", $ref)) {
                $implicitRefs[] = $ref;
            }
        }
        if (!$implicitRefs) {
            return '<info>none</info>';
        } else {
            $glue = array(
                '</comment>',
                "\n",
                '<comment>',
            );
            return $glue[2] . implode(implode('', $glue), $implicitRefs) . $glue[0];
        }
    }

    /**
     * Returns a bundle-qualified file path (eg "@MapbenderCoreBundle/dir/another-dir/file.ext") from a twig-style
     * template path. Twig can performing this conversion, but it's buried in a bunch of protected / private
     * machinery, so we have to reimplement it ourselves.
     *
     * @param string $twigPath
     * @return string
     */
    protected static function resourcePathFromTwigPath($twigPath)
    {
        $parts = explode(':', ltrim($twigPath, '@'));
        $parts[1] = "Resources/views/{$parts[1]}";
        return '@' . implode('/', $parts);
    }

    /**
     * Checks if the file named by $twigPath exists and is non-empty.
     *
     * @param string $twigPath e.g. "MapbenderCoreBundle:<view-section>:some_template.html.twig"
     * @return bool
     */
    protected function templateExists($twigPath)
    {
        // Kernel::locateResource seems to be the best general purpose resource locator
        /** Symfony file locators throw InvalidArgumentException if files are not found... */
        try {
            $realPath = $this->kernel->locateResource($this->resourcePathFromTwigPath($twigPath));
            return file_exists($realPath) && filesize($realPath);
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string[] $headers
     * @param array[] $rows
     */
    protected function renderTable(InputInterface $input, OutputInterface $output, $headers, $rows)
    {
        $tableHelper = new SymfonyStyle($input, $output);
        $tableHelper->table($headers, $rows);
    }

    /**
     * @param Element $element
     * @return string
     * @throws \ReflectionException
     */
    protected function formatAdminTemplateInfo($element)
    {
        $refl = new \ReflectionClass($element->getClass());
        /** @var ElementInterface|string $className */
        $className = $element->getClass();
        $template = $className::getFormTemplate();
        $abstractBaseClass = 'Mapbender\CoreBundle\Component\Element';
        $templateMethod = $refl->getMethod('getFormTemplate');
        $isAuto = $templateMethod->class === $abstractBaseClass;
        return $this->formatTemplatePath($element, $template, $isAuto);
    }

    /**
     * @param Element $element
     * @param string $path twig-style
     * @param bool $isAutomatic
     * @return string
     */
    protected function formatTemplatePath($element, $path, $isAutomatic)
    {
        $templateBundle = BundleUtil::extractBundleNameFromTemplatePath($path);
        $elementBundle = BundleUtil::extractBundleNameFromClassName($element->getClass());
        if ($templateBundle != $elementBundle) {
            $parts = explode(':', $path);
            $info = "<note>{$parts[0]}</note>:" . implode(':', array_slice($parts, 1));
        } else {
            $info = $path;
        }
        if (!$this->templateExists($path)) {
            $info = "<error>{$info}</error>";
        }
        if ($isAutomatic) {
            return "{$info} <comment>(auto)</comment>";
        } else {
            return $info;
        }
    }

    /**
     * @param Element $element
     * @return string
     * @throws \ReflectionException
     */
    protected function formatElementComments($element)
    {
        $issues = array();
        $rc = new \ReflectionClass($element->getClass());
        $classDoc = $rc->getDocComment();
        if (strpos($classDoc, '@deprecated') !== false) {
            $issues[] = "<comment>deprecated</comment>";
        }
        $detectOverrides = array(
            'getConfiguration' => array(null, 'error'),
            'render' => array(null, 'error'),
            'getType'=> array('comment', null),
            'getFormTemplate' => array('comment', null),
            'getFrontendTemplatePath' => array('comment', null),
            'getWidgetName' => array('comment', null),
        );

        foreach ($detectOverrides as $methodName => $treatment) {
            $isOverridden = $this->detectMethodOverride($rc, $methodName);
            $messageStyle = $treatment[intval($isOverridden)];
            if ($messageStyle !== null) {
                $messagePrefix = $isOverridden ? 'own' : 'missing';
                $message = "<$messageStyle>{$messagePrefix} {$methodName}</$messageStyle>";
                if ($issues && !(count($issues) % 2)) {
                    $message = "\n$message";
                }
                $issues[] = $message;
            }
        }
        $parentClass = $rc->getParentClass();
        if (0 !== strpos($parentClass->getNamespaceName(), 'Mapbender\CoreBundle\Component')) {
            $parentName = $parentClass->getName();
            $parentNote = "<note>parent: $parentName</note>";
        } else {
            $parentNote = "";
        }
        return trim(trim(implode(', ', $issues), "\n") . "\n{$parentNote}", "\n");
    }

    protected static function detectMethodOverride(\ReflectionClass $rc, $methodName)
    {
        $rm = $rc->getMethod($methodName);
        return $rm && ($rc->getName() == $rm->class);
    }
}
