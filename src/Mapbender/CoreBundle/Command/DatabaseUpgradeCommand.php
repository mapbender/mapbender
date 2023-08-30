<?php

namespace Mapbender\CoreBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseUpgradeCommand extends Command
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
        $this
            ->setHelp('The <info>mapbender:database:upgrade</info> command updates the database to the new schema of your mapbender version')
            ->setDescription('Removes outdated element configuration values and doctrine types')
        ;
    }


    /**
     * Execute command
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->updateMapElementConfigs($input, $output);
        $this->updateDoctrineTypes($input, $output);
    }

    protected function getObsoleteMapOptionNames()
    {
        return array(
            'imgPath',
            'wmsTileDelay',
            'minTileSize',
            'maxResolution',
        );
    }

    /**
     * Prunes obsolete configuration options from map elements
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function updateMapElementConfigs(InputInterface $input, OutputInterface $output)
    {

        /** @var EntityManager $em */
        $em = $this->managerRegistry->getManager();
        $maps = $this->managerRegistry->getRepository(Element::class)->findBy(array(
            'class' => 'Mapbender\CoreBundle\Element\Map',
        ));
        $output->writeln('Updating map element configs');
        $output->writeln('Found ' . count($maps) . ' map elements');
        $progressBar = new ProgressBar($output, count($maps));
        $updatedElements = 0;
        foreach ($maps as $map) {
            /** @var Element $map */
            $config = $map->getConfiguration();
            $progressBar->advance();
            $removedConfigs = array();
            foreach ($this->getObsoleteMapOptionNames() as $obsoleteKey) {
                if (array_key_exists($obsoleteKey, $config)) {
                    unset($config[$obsoleteKey]);
                    $removedConfigs[] = $obsoleteKey;
                }
            }
            if ($removedConfigs) {
                $progressBar->setMessage("Found obsolete configuration values " . implode(', ', $removedConfigs));
                $map->setConfiguration($config);
                $em->persist($map);
                $progressBar->setMessage('Map configuration updated');
                ++$updatedElements;
            } else {
                $progressBar->setMessage('Map element already up-to-date');
            }
        }
        $em->flush();
        $progressBar->finish();
        $output->writeln('');
        if ($updatedElements) {
            $output->writeln("Updated {$updatedElements} Map elements");
        } else {
            $output->writeln("All Map elements were already up to date");
        }
    }

    protected function updateDoctrineTypes(InputInterface $input, OutputInterface $output)
    {
        /** @var Connection $connection */
        $connection = $this->managerRegistry->getConnection();
        $output->writeln("Checking for outdated doctrine column definitions ...");
        $result = $connection->executeQuery("SELECT isc.table_name, isc.column_name FROM information_schema.columns isc "
            . "WHERE pg_catalog.col_description(format('%s.%s',isc.table_schema,isc.table_name)::regclass::oid,isc.ordinal_position) = '(DC2Type:json_array)';");
        $oldJsonArrays = $result->fetchAllAssociative();
        if (count($oldJsonArrays) === 0) {
            $output->writeln("All column definitions were up to date");
            return;
        }
        foreach ($oldJsonArrays as $oldJsonArray) {
            $connection->executeQuery("COMMENT ON COLUMN " . $oldJsonArray['table_name'] . "." . $oldJsonArray['column_name'] . " IS '(DC2Type:json)'");
        }
        $output->writeln("Updated " . count($oldJsonArrays) . " column definitions.");
    }
}

