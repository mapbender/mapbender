<?php


namespace Mapbender\CoreBundle\Component\EventListener;

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Middleware;
use Symfony\Component\Console\Event\ConsoleEvent;

class SqliteConnectionListener implements Middleware
{
    public bool $skipForeignKeys = false;

    /** @var Connection[] */
    public array $modifiedConnections = array();

    public function wrap(Driver $driver): Driver
    {
        return new SqliteConnectionMiddleWare($driver, $this);
    }

    /**
     * Also tagged as kernel.event_listener on event 'console.command'. See services.xml
     * NOTE: cannot also implement Symfony EventSubscriber because the interface is incompatible
     *       with Doctrine EventSubscriber (same method name getScubscribedEvents, but non-static vs static)
     * @param ConsoleEvent $e
     * @noinspection PhpUnused
     */
    public function onConsoleCommand(ConsoleEvent $e): void
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


    protected function undoEnableForeignKeys(Connection $connection): void
    {
        $connection->exec('PRAGMA foreign_keys = OFF;');
    }

    public function getForeignKeyBlacklistedCommandNames(): array
    {
        return ['doctrine:schema:update'];
    }
}
