<?php


namespace Mapbender\CoreBundle\Component\ElementBase;

use Mapbender\CoreBundle\Entity;

/**
 * Interface for Element components wishing to fix a known legacy config error.
 */
interface ConfigMigrationInterface
{
    /**
     * Should modify the given entity. Should *not* attempt any database interaction by itself.
     *
     * @param Entity\Element $entity
     * @return void
     */
    public static function updateEntityConfig(Entity\Element $entity);
}
