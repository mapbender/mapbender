<?php


namespace Mapbender\CoreBundle\EventHandler\InitDb;


use Doctrine\ORM\EntityManager;
use Mapbender\Component\Event\AbstractInitDbHandler;
use Mapbender\Component\Event\InitDbEvent;
use Mapbender\CoreBundle\DataFixtures\ORM\Epsg\LoadEpsgData;

/**
 * Wires Epsg database update into mapbender:database:init CLI.
 *
 * @todo v3.1: remove fixture, move logic here; fixtures are for unit tests, not for
 *             setup. Fixtures are also hard to rerun manually, unlike console commands.
 * TBD: may be more efficient to use an ObjectRepository processing the definition file
 * directly (read only, no database table).
 */
class UpdateEpsgHandler extends AbstractInitDbHandler
{
    /** @var EntityManager */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function onInitDb(InitDbEvent $event)
    {
        LoadEpsgData::doLoad($this->entityManager, $event->getOutput());
    }
}
