<?php

namespace Mapbender\CoreBundle\Component\EventListener;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;
use Doctrine\DBAL\Platforms\SqlitePlatform;

class SqliteConnectionMiddleWare extends AbstractDriverMiddleware
{
    public function __construct(Driver $wrappedDriver, protected SqliteConnectionListener $listener)
    {
        parent::__construct($wrappedDriver);
    }

    public function connect(#[\SensitiveParameter] array $params): Connection
    {
        $connection = parent::connect($params);

        if (!$this->listener->skipForeignKeys) {
            $platform = $this->getDatabasePlatform();
            if ($platform instanceof SqlitePlatform) {
                $this->enableForeignKeys($connection);
            }
        }
        return $connection;
    }

    protected function enableForeignKeys(Connection $connection): void
    {
        // remember connection so we can undo this
        $this->listener->modifiedConnections[] = $connection;
        $connection->exec('PRAGMA foreign_keys = ON;');
    }
}
