<?php


namespace Mapbender\CoreBundle\Component\EventListener;

use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Platforms\SqlitePlatform;

class SqliteConnectionListener implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return array(
            Events::postConnect,
        );
    }

    public function postConnect(ConnectionEventArgs $args)
    {
        $platform = $args->getDatabasePlatform();
        if ($platform instanceof SqlitePlatform) {
            $args->getConnection()->exec('PRAGMA foreign_keys = ON;');
        }
    }
}
