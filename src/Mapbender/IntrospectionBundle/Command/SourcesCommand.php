<?php


namespace Mapbender\IntrospectionBundle\Command;


use Mapbender\IntrospectionBundle\Component\Aggregator\Base;
use Mapbender\IntrospectionBundle\Component\Aggregator\Relation\ApplicationToSources;
use Mapbender\IntrospectionBundle\Component\Aggregator\Relation\SourceToApplications;
use Mapbender\IntrospectionBundle\Component\Collector;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\DataTreeNode;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\DataItem;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\DataItemList;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\JsonFormatting;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\YamlFormatting;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\TableHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Yaml\Yaml;

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
        $this->addOption('format', null, InputOption::VALUE_REQUIRED, 'Output format "table" (default), "yaml" or "json"');
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $formatOption = $input->getOption('format');
        if ($formatOption) {
            if (!in_array($formatOption, array('table', 'yaml', 'yml', 'json'))) {
                throw new \UnexpectedValueException("Unsupported format " . var_export($formatOption, true));
            }
        } else {
            $input->setOption('format', 'table');
        }
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

    /**
     * @param Collector $collector
     * @return array
     */
    protected function buildAppTree(Collector $collector)
    {
        $aggregate = $collector->collectApplicationInfo();
        $headers = array(
            'Application',
            'Sources',
            'Instances',
        );
        $appList = new DataItemList('applications');
        foreach ($aggregate->getRelations(true) as $appInfo) {
            $appList->addItem($this->collectAppRelation($appInfo));
        }
        foreach ($aggregate->getRelations(false) as $appInfo) {
            $appList->addItem($this->collectAppRelation($appInfo));
        }
        $unusedList = $this->buildUnusedSourcesTree($aggregate);

        return array(
            'tableHeaders'   => $headers,
            'mainList'   => $appList,
            'unusedSources' => $unusedList,
        );
    }

    /**
     * @param Collector $collector
     * @return array
     */
    protected function buildSourcesTree(Collector $collector)
    {
        $aggregate = $collector->collectSourceInfo();
        $headers = array(
            'Source',
            'Applications',
            'Instances',
        );

        $usedSourceList = new DataItemList('sources');
        foreach ($aggregate->getRelations() as $srcInfo) {
            $usedSourceList->addItem($this->collectSourceRelation($srcInfo));
        }
        $dataTree = new DataTreeNode(null);
        $unusedList = $this->buildUnusedSourcesTree($aggregate);
        $dataTree->addItem($usedSourceList);
        $dataTree->addItem($unusedList);

        return array(
            'tableHeaders'  => $headers,
            'mainList'      => $usedSourceList,
            'unusedSources' => $unusedList,
        );
    }


    /**
     * @param Base $aggregator
     * @return DataTreeNode
     */
    protected function buildUnusedSourcesTree($aggregator)
    {
        $itemTree = new DataItemList('unused');
        foreach ($aggregator->getUnusedSources() as $source) {
            $itemTree->addItem(new DataTreeNode($source->getId(), $source->getTitle()));
        }
        return $itemTree;
    }

    /**
     * @param OutputInterface $output
     * @param DataItemList $sourceItemList
     */
    protected function displayUnusedSources(OutputInterface $output, $sourceItemList)
    {
        $sourceItems = $sourceItemList->getItems();
        if ($sourceItems) {
            $output->writeln("<comment>Unused sources:</comment>");
            foreach ($sourceItems as $unusedSourceItem) {
                $output->writeln("  {$unusedSourceItem->toDisplayable()}");
            }
        } else {
            $output->writeln("<info>No unused sources!</info>");
        }
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $collector = new Collector($this->getContainer());
        if ($this->bucketBy == 'application') {
            $result = $this->buildAppTree($collector);

        } else {
            $result = $this->buildSourcesTree($collector);
        }

        /** @var DataItem $mainList */
        $mainList = $result['mainList'];

        $outputFormat = $input->getOption('format');
        if ($outputFormat == 'table') {
            if (!$input->getOption('unused-only')) {
                $this->renderTable($output, $result['tableHeaders'], $mainList->toGrid());
            }
            if (!$input->getOption('no-unused')) {
                $this->displayUnusedSources($output, $result['unusedSources']);
            }
        } else {
            // Yaml and Json both require building an array representation
            if ($outputFormat == 'json') {
                $format = new JsonFormatting('title');
            } else {
                $format = new YamlFormatting('title');
            }
            $displayTree = new DataTreeNode(null);
            if (!$input->getOption('unused-only')) {
                $displayTree->addItem($result['mainList']);
            }
            if (!$input->getOption('no-unused')) {
                $displayTree->addItem($result['unusedSources']);
            }

            $dataArray = $displayTree->toArray($format);
            if ($outputFormat == 'json') {
                $output->writeln(json_encode($dataArray));
            } else {
                $output->writeln(Yaml::dump($dataArray, 6000, 4));
            }
        }
    }

    /**
     * @param ApplicationToSources $appInfo
     * @return DataTreeNode
     */
    protected function collectAppRelation(ApplicationToSources $appInfo)
    {
        $application = $appInfo->getApplication();
        $appItem = new DataTreeNode($application->getId(), $application->getTitle());
        $appItem->addFlag('publised', $application->isPublished(), null, 'comment', 'not published');
        $sourcesList = new DataItemList('sources');

        foreach ($appInfo->getSourceRelations() as $srcRelation) {
            $source = $srcRelation->getSource();
            $sourceItem = new DataTreeNode($source->getId(), $source->getTitle());
            $instanceList = new DataItemList('instances');
            foreach ($srcRelation->getSourceInstances() as $sourceInstance) {
                $instanceItem = new DataItem($sourceInstance->getId(), $sourceInstance->getTitle());
                $instanceItem->addFlag('enabled', $sourceInstance->getEnabled(), null, 'comment', 'disabled');
                $instanceList->addItem($instanceItem);
            }
            $sourceItem->addItem($instanceList);
            $sourcesList->addItem($sourceItem);
        }
        $appItem->addItem($sourcesList);
        return $appItem;
    }

    /**
     * @param SourceToApplications $relation
     * @return DataTreeNode
     */
    protected function collectSourceRelation(SourceToApplications $relation)
    {
        $source = $relation->getSource();

        $sourceItem = new DataTreeNode($source->getId(), $source->getTitle());
        $appList = new DataItemList('applications');

        foreach ($relation->getApplicationRelations() as $appRelation) {
            $app = $appRelation->getApplication();
            $appItem = new DataTreeNode($app->getId(), $app->getTitle());
            $appItem->addFlag('publised', $app->isPublished(), null, 'comment', 'not published');
            $instanceList = new DataItemList('instances');
            foreach ($appRelation->getSourceInstances() as $sourceInstance) {
                $instanceItem = new DataItem($sourceInstance->getId(), $sourceInstance->getTitle());
                $instanceItem->addFlag('enabled', $sourceInstance->getEnabled(), null, 'comment', 'disabled');
                $instanceList->addItem($instanceItem);
            }
            $appItem->addItem($instanceList);
            $appList->addItem($appItem);
        }
        $sourceItem->addItem($appList);
        return $sourceItem;
    }

    /**
     * @param \Mapbender\CoreBundle\Entity\Source[] $sources
     * @return string[][]
     */
    protected function sourcesToArray($sources)
    {
        $rv = array();
        foreach ($sources as $source) {
            $rv[] = array(
                'id' => strval($source->getId()),
                'name' => strval($source->getTitle()),
            );
        }
        return $rv;
    }

    /**
     * @param OutputInterface $output
     * @param string[] $headers
     * @param string[][] $rows
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
