<?php

namespace Mapbender\CoreBundle\Component\EventListener;

use Doctrine\DBAL\Driver;
use Doctrine\DBAL\Driver\AbstractOracleDriver;
use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Driver\Middleware;
use Doctrine\DBAL\Driver\Middleware\AbstractDriverMiddleware;

/**
 * Ensures DBAL-required session variables (like date format) are set on
 * Oracle connections. Unlike upstream OracleSessionInit, this middleware
 * automatically checks if the connection is actually an Oracle connection.
 * This allows mixing multiple connections to different database servers,
 * without the need to preconfigure the middleware to only process certain
 * connections by name.
 *
 * Originally from wheregroup/doctrine-dbal-shims, integrated into CoreBundle.
 */
class OnDemandOracleSessionInit implements Middleware
{
    public function wrap(Driver $driver): Driver
    {
        if (!($driver instanceof AbstractOracleDriver)) {
            return $driver;
        }

        return new class ($driver) extends AbstractDriverMiddleware {
            public function connect(
                #[\SensitiveParameter]
                array $params,
            ): Connection {
                $connection = parent::connect($params);

                $connection->exec(
                    'ALTER SESSION SET'
                    . " NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"
                    . " NLS_TIME_FORMAT = 'HH24:MI:SS'"
                    . " NLS_TIMESTAMP_FORMAT = 'YYYY-MM-DD HH24:MI:SS'"
                    . " NLS_TIMESTAMP_TZ_FORMAT = 'YYYY-MM-DD HH24:MI:SS TZH:TZM'"
                    . " NLS_NUMERIC_CHARACTERS = '.,'",
                );

                return $connection;
            }
        };
    }
}
