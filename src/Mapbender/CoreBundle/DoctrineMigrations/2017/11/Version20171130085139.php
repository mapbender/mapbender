<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration to modify image path
 */
class Version20171130085139 extends AbstractMigration
{
    private $configuration = [
        'className'    => 'Mapbender\CoreBundle\Element\Map',
        'oldImagePath' => 'bundles/mapbendercore/mapquery/lib/openlayers/img',
        'newImagePath' => 'components/mapquery/lib/openlayers/img',
    ];


    /**
     * Check if there are some map elements before migrate
     *
     * @param Schema $schema
     */
    public function preUp(Schema $schema)
    {
        $this->checkMapElementsQuantity();
    }


    /**
     * Change the image path from the old one to the new one
     *
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->write('Updating map elements image path values from ' . $this->configuration['oldImagePath'] . ' to ' . $this->configuration['newImagePath']);

        $this
            ->addSql(
                "UPDATE mb_core_element SET configuration = REPLACE(configuration, :oldImagePath, :newImagePath) WHERE class = :className",
                $this->configuration
            );

        $this->write('All image path values are successfully updated');
    }


    /**
     * Check if there are some map elements before revert the migration
     *
     * @param Schema $schema
     */
    public function preDown(Schema $schema)
    {
        $this->checkMapElementsQuantity();
    }


    /**
     * Change the image path from the new one to the old one
     *
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->write('Revert map elements image path values from ' . $this->configuration['newImagePath'] . ' to ' . $this->configuration['oldImagePath']);

        $this
            ->addSql(
                "UPDATE mb_core_element SET configuration = REPLACE(configuration, :newImagePath, :oldImagePath) WHERE class = :className",
                $this->configuration
            );

        $this->write('All image path values are successfully updated');
    }


    /**
     * Check if we have map elements to update
     */
    private function checkMapElementsQuantity()
    {
        $result = $this
            ->connection
            ->fetchAssoc(
                "SELECT COUNT(*) as elementsQuantity FROM mb_core_element WHERE class = :className",
                $this->configuration
            );

        $elementsQuantity = $result['elementsQuantity'];

        $this->write('Found ' . $elementsQuantity . ' map elements');
        $this->skipIf($elementsQuantity == 0, 'No rows has been found');
    }
}
