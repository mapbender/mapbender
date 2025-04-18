<?php

namespace Mapbender\CoreBundle\Command;

use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use FOM\UserBundle\Entity\UserLogEntry;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\PrintBundle\Entity\QueuedPrintJob;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseUpgradeCommand extends Command
{

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct(null);
    }

    protected function configure(): void
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

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->updateMapElementConfigs($input, $output);
        $this->updateDoctrineTypes($input, $output);
        $this->updateButtonTypes($input, $output);
        return 0;
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

        $maps = $this->em->getRepository(Element::class)->findBy(array(
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
                $this->em->persist($map);
                $progressBar->setMessage('Map configuration updated');
                ++$updatedElements;
            } else {
                $progressBar->setMessage('Map element already up-to-date');
            }
        }
        $this->em->flush();
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
        // doctrine dbal 3 removed the json_array type as a breaking change. This means it also does not support migrating it
        // anymore, so we need to do it on our own

        $connection = $this->em->getConnection();
        $output->writeln("Checking for outdated doctrine column definitions ...");

        $platform = $connection->getDriver()->getDatabasePlatform();
        // for postgresql we can update all fields at once, including fields from custom tables
        if ($platform instanceof PostgreSqlPlatform) {
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
        } elseif ($platform instanceof MySQLPlatform) {
            // for other DMS we just update the known fields. Custom tables using json_array must be updated manually
            $connection->executeQuery("ALTER TABLE fom_user_log MODIFY context JSON COMMENT '(DC2Type:json)'");
            $connection->executeQuery("ALTER TABLE mb_print_queue MODIFY payload JSON COMMENT '(DC2Type:json)'");
        } elseif ($platform instanceof OraclePlatform) {
            $connection->executeQuery("COMMENT ON COLUMN fom_user_log.context IS '(DC2Type:json)'");
            $connection->executeQuery("COMMENT ON COLUMN mb_print_queue.payload IS '(DC2Type:json)'");
        } elseif ($platform instanceof SqlitePlatform) {
            $connection->beginTransaction();
            // sqlite does not support first-class comments, instead it stores them in the sqlite_master table which is not editable
            // therefore, rename the table, create the table as new and transfer the data
            foreach (['fom_user_log' => UserLogEntry::class, 'mb_print_queue' => QueuedPrintJob::class] as $tableName => $class) {
                $connection->executeQuery("ALTER TABLE $tableName RENAME TO tmp_$tableName");

                // drop indices, they will be recreated under the same name resulting in an exception
                $indices = $connection->executeQuery("SELECT name FROM sqlite_master WHERE type == 'index' AND tbl_name == 'tmp_$tableName'")->fetchFirstColumn();
                foreach ($indices as $index) {
                    $connection->executeQuery("DROP INDEX $index");
                }

                $schemaTool = new SchemaTool($this->em);
                $classMetadata = $this->em->getClassMetadata($class);
                $sqls = $schemaTool->getCreateSchemaSql([$classMetadata]);
                foreach ($sqls as $sql) {
                    if (!str_contains($sql, 'acl')) $connection->executeQuery($sql);
                }
                $connection->executeQuery("INSERT INTO $tableName SELECT * FROM tmp_$tableName;");
                $connection->executeQuery("DROP TABLE tmp_$tableName;");
            }
            $connection->commit();
        } else {
            // We support the most common platforms, all other must be migrated manually
            $output->writeln("Please manually updated all comments from (DC2Type:json_array) to (DC2Type:json) in your database columns. This includes the mapbender columns fom_user_log.context and mb_print_queue.payload.");
        }

    }

    protected function updateButtonTypes(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Updating old button classes …');
        $oldButtons = $this->em->getRepository(Element::class)->findBy(array(
            'class' => 'Mapbender\CoreBundle\Element\Button',
        ));

        $output->writeln('Found ' . count($oldButtons) . ' Mapbender\CoreBundle\Element\Button elements');
        foreach ($oldButtons as $oldButton) {
            if (str_contains(json_encode($oldButton->getConfiguration()), 'http')) {
                $oldButton->setClass("Mapbender\\CoreBundle\\Element\\LinkButton");
            } else {
                $oldButton->setClass("Mapbender\\CoreBundle\\Element\\ControlButton");
            }
        }
        $this->em->flush();
    }

}

