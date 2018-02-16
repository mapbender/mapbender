<?php


namespace Mapbender\IntrospectionBundle\Command;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\IntrospectionBundle\Component\Aggregator\Relation\ApplicationToSources;
use Mapbender\IntrospectionBundle\Component\Aggregator\Relation\SourceToApplications;
use Mapbender\IntrospectionBundle\Component\Aggregator\Relation\SourceToSourceInstances;
use Mapbender\IntrospectionBundle\Component\Collector;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;

class SourcesCommand extends ContainerAwareCommand
{
    protected $buckets = array();
    protected $bucketBy = 'application';

    protected function configure()
    {
        $this->setName('mapbender:inspect:source:usage');
        $this->addOption('by-app', null, InputOption::VALUE_NONE, 'Group by application (default)');
        $this->addOption('by-source', null, InputOption::VALUE_NONE, 'Group by source');
        $this->addOption('unused-only', null, InputOption::VALUE_NONE, 'Display only unused sources');
        $this->addOption('no-unused', null, InputOption::VALUE_NONE, 'Do not display unused sources');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('by-source')) {
            if ($input->getOption('by-app')) {
                throw new \RuntimeException("Options --by-app and --by-source are mutually exclusive!");
            }
            $this->bucketBy = 'source';
        } else {
            $this->bucketBy = 'application';
        }
        if ($input->getOption('unused-only') && $input->getOption('no-unused')) {
            throw new \RuntimeException("Options --unused-only and --no-unused are mutually exclusive!");
        }

        $this->buckets = array();
        $noteStyle = new OutputFormatterStyle('white', 'blue');
        $output->getFormatter()->setStyle('note', $noteStyle);
    }

    protected function executeByApp(InputInterface $input, OutputInterface $output)
    {
        $collector = new Collector($this->getContainer());
        $aggregate = $collector->collectApplicationInfo();
        $headers = array(
            'Application',
            'Sources',
            'Instances',
        );
        $rows = array();
        foreach ($aggregate->getRelations(true) as $appInfo) {
            foreach ($this->formatAppRelation($appInfo) as $subRow) {
                $rows[] = $subRow;
            }
        }
        foreach ($aggregate->getRelations(false) as $appInfo) {
            foreach ($this->formatAppRelation($appInfo) as $subRow) {
                $rows[] = $subRow;
            }
        }

        if (!$input->getOption('unused-only')) {
            $this->renderTable($output, $headers, $rows);
        }
        if (!$input->getOption('no-unused')) {
            $this->displayUnusedSources($output, $aggregate->getUnusedSources());
        }
    }

    protected function executeBySource(InputInterface $input, OutputInterface $output)
    {
        $collector = new Collector($this->getContainer());
        $aggregate = $collector->collectSourceInfo();
        $headers = array(
            'Source',
            'Applications',
            'Instances',
        );
        $rows = array();
        foreach ($aggregate->getRelations() as $srcInfo) {
            foreach ($this->formatSourceRelation($srcInfo) as $subRow) {
                $rows[] = $subRow;
            }
        }
        if (!$input->getOption('unused-only')) {
            $this->renderTable($output, $headers, $rows);
        }
        if (!$input->getOption('no-unused')) {
            $this->displayUnusedSources($output, $aggregate->getUnusedSources());
        }
    }

    /**
     * @param OutputInterface $output
     * @param Source[] $sources
     */
    protected function displayUnusedSources(OutputInterface $output, $sources)
    {
        if ($sources) {
            ksort($sources);
            $output->writeln("<comment>Unused sources:</comment>");
            foreach ($sources as $id => $unusedSource) {
                $output->writeln("  $id: {$unusedSource->getTitle()}");
            }
        } else {
            $output->writeln("<info>No unused sources!</info>");
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->bucketBy == 'application') {
            $this->executeByApp($input, $output);
        } else {
            $this->executeBySource($input, $output);
        }
    }

    /**
     * @param ApplicationToSources $appInfo
     * @return string[] one row of cells
     */
    protected function formatAppRelation(ApplicationToSources $appInfo)
    {
        $application = $appInfo->getApplication();
        $appText = "{$application->getId()}: {$application->getTitle()}";
        if (!$application->isPublished()) {
            $appText = "<comment>$appText <note>(not published)</note></comment>";
        }
        $rowsOut = array();
        $baseCells = array(
            $appText,
        );
        foreach ($appInfo->getSourceRelations() as $srcRelation) {
            $extraCells = $this->formatSourceInstanceRelation($srcRelation);
            $rowsOut[] = array_merge($baseCells, $extraCells);
            $baseCells = array("");
        }
        return $rowsOut;
    }

    /**
     * @param ApplicationToSources $appRelation
     * @return string[] two cells of formatted text
     */
    protected function formatAppRelationForSource($appRelation)
    {
        $app = $appRelation->getApplication();
        $appBaseText = "{$app->getId()}: {$app->getTitle()}";
        $appText = $this->formatApplicationText($appBaseText, $app->isPublished());

        $instanceTexts = array();
        foreach ($appRelation->getSourceInstances() as $sourceInstance) {
            $instanceTexts[] = $this->formatSourceInstanceText($sourceInstance, false);
        }
        if (!$instanceTexts) {
            return array("$appText", "<error>-- none --</error>");
        } else {
            $appRows = array_pad(array($appText), count($instanceTexts), "");
            return array(implode("\n", $appRows), implode("\n", $instanceTexts));
        }
    }

    protected function formatSourceRelation(SourceToApplications $relation)
    {
        $source = $relation->getSource();
        $rowsOut = array();
        $baseCells = array(
            $this->formatSourceText($source),
        );
        foreach ($relation->getApplicationRelations() as $appRelation) {
            $extraCells = $this->formatAppRelationForSource($appRelation);
            $rowsOut[] = array_merge($baseCells, $extraCells);
            $baseCells = array("");
        }
        return $rowsOut;
    }

    protected function formatSourceInstanceRelation(SourceToSourceInstances $relation)
    {
        $sourceText = $this->formatSourceText($relation->getSource());
        $instanceTexts = array();
        foreach ($relation->getSourceInstances() as $sourceInstance) {
            $instanceTexts[] = $this->formatSourceInstanceText($sourceInstance, false);
        }
        if (!$instanceTexts) {
            return array("$sourceText", "<error>-- none --</error>");
        } else {
            $appRows = array_pad(array($sourceText), count($instanceTexts), "");
            return array(implode("\n", $appRows), implode("\n", $instanceTexts));
        }
    }

    protected static function formatSourceText(Source $source, $enabled = true)
    {
        $baseText = "{$source->getId()}: {$source->getTitle()}";
        if (!$enabled) {
            return "<comment>{$baseText} <note>(disabled)</note></comment>";
        } else {
            return $baseText;
        }
    }

    protected static function formatApplicationText($text, $published)
    {
        if (!$published) {
            return "<comment>$text <note>(not published)</note></comment>";
        } else {
            return $text;
        }
    }

    protected static function formatSourceInstanceText(SourceInstance $sourceInstance, $includeSourceId = true)
    {
        if ($includeSourceId) {
            $sourceId = $sourceInstance->getSource()->getId();
            $sourceIdText = "(src {$sourceId})";
        } else {
            $sourceIdText = "";
        }
        $baseText = "{$sourceInstance->getId()}{$sourceIdText}: {$sourceInstance->getTitle()}";
        if ($sourceInstance->getEnabled()) {
            return $baseText;
        } else {
            return "<comment>$baseText <note>(disabled)</note></comment>";
        }
    }

    /**
     * @param OutputInterface $output
     * @param string[] $headers
     * @param array[] $rows
     */
    protected function renderTable(OutputInterface $output, $headers, $rows)
    {
        if (class_exists('Symfony\Component\Console\Helper\TableHelper')) {
            $th = $this->getTableHelper();
            $th->setHeaders($headers);
            $th->setRows($rows);
            $th->render($output);
        } else {
            $symfonyVersion = Kernel::VERSION;
            throw new \RuntimeException("Table rendering support gone in Symfony $symfonyVersion");
        }
    }

    /**
     * @return TableHelper
     * @todo: this will be gone in Symfony 3.0
     */
    protected function getTableHelper()
    {
        /** @var TableHelper $table */
        $table = $this->getHelper('table');
        $table->setCellRowFormat('%s');
        $table->setCellHeaderFormat('%s');
        return $table;
    }
}
