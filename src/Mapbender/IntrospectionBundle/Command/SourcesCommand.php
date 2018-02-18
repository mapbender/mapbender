<?php


namespace Mapbender\IntrospectionBundle\Command;


use Mapbender\IntrospectionBundle\Component\Aggregator\Application;
use Mapbender\IntrospectionBundle\Component\Aggregator\Relation\ApplicationToSources;
use Mapbender\IntrospectionBundle\Component\Aggregator\Relation\SourceToApplications;
use Mapbender\IntrospectionBundle\Component\Aggregator\Source;
use Mapbender\IntrospectionBundle\Component\Collector;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\DataGroup;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\DataItem;
use Mapbender\IntrospectionBundle\Entity\Utils\Command\DataRootGroup;
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
        $tree = new DataRootGroup();
        foreach ($aggregate->getRelations(true) as $appInfo) {
            $tree->addItem($this->collectAppRelation($appInfo));
        }
        foreach ($aggregate->getRelations(false) as $appInfo) {
            $tree->addItem($this->collectAppRelation($appInfo));
        }
        return array(
            'tree'      => $tree,
            'headers'   => $headers,
            'aggregate' => $aggregate,
        );
    }

    /**
     * @param Collector $collector
     * @return array
     */
    protected function buildSourceTree(Collector $collector)
    {
        $aggregate = $collector->collectSourceInfo();
        $headers = array(
            'Source',
            'Applications',
            'Instances',
        );

        $tree = new DataRootGroup();
        foreach ($aggregate->getRelations() as $srcInfo) {
            $tree->addItem($this->collectSourceRelation($srcInfo));
        }
        return array(
            'tree'      => $tree,
            'headers'   => $headers,
            'aggregate' => $aggregate,
        );
    }

    /**
     * @param OutputInterface $output
     * @param \Mapbender\CoreBundle\Entity\Source[] $sources
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
        $collector = new Collector($this->getContainer());
        if ($this->bucketBy == 'application') {
            $result = $this->buildAppTree($collector);
            $sectionName0 = 'applications';
            $arraySubHeadings = array('sources', 'instances');

        } else {
            $result = $this->buildSourceTree($collector);
            $sectionName0 = 'sources';
            $arraySubHeadings = array('applications', 'instances');
        }

        /** @var DataItem $tree */
        $tree = $result['tree'];
        /** @var Application|Source $aggregate */
        $aggregate = $result['aggregate'];

        $outputFormat = $input->getOption('format');
        if ($outputFormat == 'table') {
            if (!$input->getOption('unused-only')) {
                $tree = $result['tree'];
                $this->renderTable($output, $result['headers'], $tree->toGrid());
            }
            if (!$input->getOption('no-unused')) {
                /** @var Application|Source $aggregate */
                $aggregate = $result['aggregate'];
                $this->displayUnusedSources($output, $aggregate->getUnusedSources());
            }
        } else {
            // Yaml and Json both require building an array representation
            $dataArray = array();
            if ($outputFormat == 'json') {
                $format = new JsonFormatting('title');
            } else {
                $format = new YamlFormatting('title');
            }

            if (!$input->getOption('unused-only')) {
                $dataArray[$sectionName0] = $tree->toArray($format, $arraySubHeadings);
            }
            if (!$input->getOption('no-unused')) {
                $dataArray['unusedSources'] = $this->sourcesToArray($aggregate->getUnusedSources());
            }
            if ($outputFormat == 'json') {
                $output->writeln(json_encode($dataArray));
            } else {
                $output->writeln(Yaml::dump($dataArray, 6000, 4));
            }
        }
    }

    /**
     * @param ApplicationToSources $appInfo
     * @return DataGroup
     */
    protected function collectAppRelation(ApplicationToSources $appInfo)
    {
        $application = $appInfo->getApplication();
        $appItem = new DataGroup($application->getId(), $application->getTitle());
        $appItem->addFlag('publised', $application->isPublished(), null, 'comment', 'not published');

        foreach ($appInfo->getSourceRelations() as $srcRelation) {
            $source = $srcRelation->getSource();
            $sourceItem = new DataGroup($source->getId(), $source->getTitle());
            foreach ($srcRelation->getSourceInstances() as $sourceInstance) {
                $instanceItem = new DataItem($sourceInstance->getId(), $sourceInstance->getTitle());
                $instanceItem->addFlag('enabled', $sourceInstance->getEnabled(), null, 'comment', 'disabled');
                $sourceItem->addItem($instanceItem);
            }
            $appItem->addItem($sourceItem);
        }
        return $appItem;
    }

    /**
     * @param SourceToApplications $relation
     * @return DataGroup
     */
    protected function collectSourceRelation(SourceToApplications $relation)
    {
        $source = $relation->getSource();

        $sourceGroup = new DataGroup($source->getId(), $source->getTitle());
        foreach ($relation->getApplicationRelations() as $appRelation) {
            $app = $appRelation->getApplication();
            $appItem = new DataGroup($app->getId(), $app->getTitle());
            $appItem->addFlag('publised', $app->isPublished(), null, 'comment', 'not published');
            $sourceGroup->addItem($appItem);

            foreach ($appRelation->getSourceInstances() as $sourceInstance) {
                $instanceItem = new DataItem($sourceInstance->getId(), $sourceInstance->getTitle());
                $instanceItem->addFlag('enabled', $sourceInstance->getEnabled(), null, 'comment', 'disabled');
                $appItem->addItem($instanceItem);
            }
        }
        return $sourceGroup;
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
