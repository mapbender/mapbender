<?php

namespace Mapbender\CoreBundle\Command;

use Doctrine\DBAL\Connection;
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
        // converts PHP-serialized data to JSON and adjusts column types
        $this->updateArrayToJsonTypes($input, $output);
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

        $platform = $connection->getDatabasePlatform();
        // for postgresql we can update all fields at once, including fields from custom tables
        if ($platform instanceof PostgreSQLPlatform) {
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

    /**
     * Migrates columns that used the Doctrine 'array'/'object' type to the 'json' type (required for DBAL 4).
     * For each affected column, existing PHP-serialized data is converted to JSON before the column type is altered.
     */
    protected function updateArrayToJsonTypes(InputInterface $input, OutputInterface $output): void
    {
        $connection = $this->em->getConnection();
        $output->writeln("Migrating array/object typed columns to JSON (DBAL 4 compatibility) ...");

        $platform = $connection->getDatabasePlatform();

        // Columns to migrate from Doctrine 'array'/'object' type to JSON
        // format: tableName => [columnName, ...]
        $jsonMigrations = [
            'mb_core_application'             => ['extra_assets'],
            'mb_core_element'                 => ['configuration'],
            'mb_core_regionproperties'        => ['properties'],
            'mb_core_viewmanager_state'       => ['layerset_states', 'source_states'],
            'mb_wms_wmsinstance'              => ['dimensions', 'vendorspecifics'],
            'mb_wms_wmslayersource'           => [
                'latlonBounds', 'boundingBoxes', 'srs', 'styles', 'scale',
                'attribution', 'identifier', 'authority', 'metadataUrl',
                'dimension', 'dataUrl', 'featureListUrl',
            ],
            'mb_wms_wmssource'                => [
                'exceptionFormats', 'getCapabilities', 'getMap', 'getFeatureInfo',
                'describeLayer', 'getLegendGraphic', 'getStyles', 'putStyles',
            ],
            'mb_wmts_theme'                   => ['layerrefs'],
            'mb_wmts_tilematrixset'           => ['tilematrices'],
            'mb_wmts_wmtslayersource'         => [
                'latlonBounds', 'boundingBoxes', 'styles', 'infoformats',
                'tilematrixSetlinks', 'resourceUrl',
            ],
            'mb_wmts_wmtssource'              => ['getTile', 'getFeatureInfo'],
        ];

        // Columns where only the DC2Type comment needs to be cleared (type is already correct in the schema)
        $commentOnlyColumns = [
            'mb_ogc_api_features_instancelayer' => ['secondary_style_ids'],
        ];

        if ($platform instanceof PostgreSQLPlatform) {
            $migrated = 0;

            foreach ($jsonMigrations as $table => $columns) {
                foreach ($columns as $column) {
                    // PostgreSQL folds unquoted identifiers to lowercase; use lowercase for all lookups and SQL
                    $colDb = strtolower($column);
                    $currentType = $this->getColumnDataType($connection, $table, $colDb);
                    if ($currentType === null) {
                        $output->writeln("  Skipping $table.$column (column not found in schema)");
                        continue;
                    }
                    if (in_array($currentType, ['json', 'jsonb'], true)) {
                        // Already JSON — just ensure the DC2Type comment is cleared
                        $connection->executeQuery("COMMENT ON COLUMN \"$table\".\"$colDb\" IS ''");
                        continue;
                    }

                    // Convert rows that still contain PHP-serialized data to valid JSON before ALTER
                    $dataConverted = $this->convertPhpSerializedColumnToJson($connection, $table, $colDb, $output);
                    if ($dataConverted > 0) {
                        $output->writeln("  Converted $dataConverted PHP-serialized value(s) in $table.$column to JSON");
                    }

                    $output->writeln("  Migrating $table.$column ($currentType -> JSON)");
                    // NULLIF handles empty strings; ::json performs the cast
                    $connection->executeQuery(
                        "ALTER TABLE \"$table\" ALTER \"$colDb\" TYPE JSON USING NULLIF(\"$colDb\", '')::json"
                    );
                    $connection->executeQuery("COMMENT ON COLUMN \"$table\".\"$colDb\" IS ''");
                    $migrated++;
                }
            }

            // Clear residual DC2Type comments on columns that are already the correct type
            foreach ($commentOnlyColumns as $table => $columns) {
                foreach ($columns as $column) {
                    $colDb = strtolower($column);
                    $connection->executeQuery("COMMENT ON COLUMN \"$table\".\"$colDb\" IS ''");
                }
            }

            if ($migrated > 0) {
                $output->writeln("Migrated $migrated columns.");
            } else {
                $output->writeln("All columns were already up to date.");
            }

        } elseif ($platform instanceof MySQLPlatform) {
            // MySQL: MODIFY column to JSON; check current type to stay idempotent
            $migrated = 0;

            foreach ($jsonMigrations as $table => $columns) {
                foreach ($columns as $column) {
                    try {
                        $result = $connection->executeQuery(
                            "SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_NAME = :table AND COLUMN_NAME = :column AND TABLE_SCHEMA = DATABASE()",
                            ['table' => $table, 'column' => $column]
                        );
                        $currentType = $result->fetchOne();
                        if ($currentType === 'json') {
                            continue;
                        }
                        // Convert PHP-serialized data before altering the column
                        $dataConverted = $this->convertPhpSerializedColumnToJson($connection, $table, $column, $output);
                        if ($dataConverted > 0) {
                            $output->writeln("  Converted $dataConverted PHP-serialized value(s) in $table.$column to JSON");
                        }
                        $output->writeln("  Migrating $table.$column to JSON");
                        $connection->executeQuery("ALTER TABLE `$table` MODIFY `$column` JSON COMMENT '(DC2Type:json)'");
                        $migrated++;
                    } catch (\Exception $e) {
                        $output->writeln("  Could not migrate $table.$column: " . $e->getMessage());
                    }
                }
            }

            if ($migrated > 0) {
                $output->writeln("Migrated $migrated columns.");
            } else {
                $output->writeln("All columns were already up to date.");
            }

        } elseif ($platform instanceof SqlitePlatform) {
            // SQLite uses dynamic typing, ALTER COLUMN TYPE is not supported.
            // No schema changes are needed for JSON compatibility.
            // Note: existing data stored as PHP-serialized strings (from Doctrine 'array' type)
            // would need manual data conversion if it exists.
            $output->writeln("SQLite: no schema type changes required (dynamic typing). All columns already compatible.");

        } else {
            $output->writeln("Unsupported platform. Please manually migrate array/object columns to JSON type.");
        }
    }

    /**
     * For a given table column, reads all rows that still contain PHP-serialized data,
     * unserializes them in PHP, re-encodes them as JSON and writes them back to the database.
     * This must be done before altering the column type to JSON.
     * Returns the number of rows that were converted.
     */
    private function convertPhpSerializedColumnToJson(
        Connection $connection,
        string $table,
        string $column,
        OutputInterface $output
    ): int {
        $result = $connection->executeQuery(
            "SELECT id, \"$column\" FROM \"$table\" WHERE \"$column\" IS NOT NULL AND \"$column\" != ''"
        );
        $rows = $result->fetchAllAssociative();

        $converted = 0;
        foreach ($rows as $row) {
            $value = $row[$column];
            if (!$this->isPhpSerialized($value)) {
                continue;
            }
            $unserialized = @unserialize($value);
            if ($unserialized === false && $value !== 'b:0;') {
                $output->writeln("  WARNING: Could not unserialize value in $table.$column for id={$row['id']}, skipping row");
                continue;
            }
            $json = json_encode($unserialized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $connection->executeQuery(
                "UPDATE \"$table\" SET \"$column\" = :json WHERE id = :id",
                ['json' => $json, 'id' => $row['id']]
            );
            $converted++;
        }

        return $converted;
    }

    /**
     * Returns true if the given string appears to be PHP-serialized data (produced by serialize()).
     * PHP serialization uses prefixes like a: (array), O: (object), s: (string), i: (int), etc.
     * Special case: serialize(null) produces 'N;' (no colon, just semicolon).
     */
    private function isPhpSerialized(string $value): bool
    {
        // serialize(null) === 'N;'  — no colon, must be checked explicitly
        if ($value === 'N;') {
            return true;
        }
        // All other PHP-serialized types use "type_char:..." format
        return (bool) preg_match('/^[abiCdOrsS]:/', $value);
    }

    /**
     * Returns the data_type of a column from information_schema, or null if the column does not exist.
     * Works for PostgreSQL and MySQL.
     */
    private function getColumnDataType(Connection $connection, string $table, string $column): ?string
    {
        $result = $connection->executeQuery(
            "SELECT data_type FROM information_schema.columns WHERE table_name = :table AND column_name = :column",
            ['table' => $table, 'column' => $column]
        );
        $row = $result->fetchAssociative();
        return $row ? $row['data_type'] : null;
    }
}
