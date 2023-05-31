<?php


namespace Mapbender\CoreBundle\Component\EventListener;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Event\ConnectionEventArgs;
use Doctrine\DBAL\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Symfony\Component\Console\Event\ConsoleEvent;

class SqliteConnectionListener implements EventSubscriber
{
    protected $skipForeignKeys = false;

    /** @var Connection[] */
    protected $modifiedConnections = array();

    public function getSubscribedEvents()
    {
        return array(
            Events::postConnect,
        );
    }

    public function postConnect(ConnectionEventArgs $args)
    {
        if (!$this->skipForeignKeys) {
            $platform = $args->getConnection()->getDatabasePlatform();
            if ($platform instanceof SqlitePlatform) {
                $this->enableForeignKeys($args->getConnection());
            }
        }
    }

    /**
     * Also tagged as kernel.event_listener on event 'console.command'. See services.xml
     * NOTE: cannot also implement Symfony EventSubscriber because the interface is incompatible
     *       with Doctrine EventSubscriber (same method name getScubscribedEvents, but non-static vs static)
     * @param ConsoleEvent $e
     */
    public function onConsoleCommand(ConsoleEvent $e)
    {
        $commandName = $e->getCommand()->getName();
        if (in_array($commandName, $this->getForeignKeyBlacklistedCommandNames())) {
            $this->skipForeignKeys = true;
            foreach ($this->modifiedConnections as $connection) {
                $this->undoEnableForeignKeys($connection);
            }
            $this->modifiedConnections = array();
        }
    }

    protected function enableForeignKeys(Connection $connection)
    {
        // remember connection so we can undo this
        $this->modifiedConnections[] = $connection;
        $connection->executeStatement('PRAGMA foreign_keys = ON;');
    }

    protected function undoEnableForeignKeys(Connection $connection)
    {
        $connection->executeStatement('PRAGMA foreign_keys = OFF;');
    }

    public function getForeignKeyBlacklistedCommandNames()
    {
        return array(
            'doctrine:schema:update',
        );
    }
}
