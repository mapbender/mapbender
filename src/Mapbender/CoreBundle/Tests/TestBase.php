<?php

namespace Mapbender\CoreBundle\Tests;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\RegionProperties;
use Mapbender\CoreBundle\Mapbender;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Class ApplicationTest
 *
 * @package Mapbender\CoreBundle\Tests
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class TestBase extends WebTestCase
{
    public function setUp()
    {
        \ComposerBootstrap::allowWriteLogs()

        $kernel = $this->getContainer()->get("kernel");

        self::runCommand('cache:clear --no-debug');
        self::runCommand('cache:warmup --no-debug');
        self::runCommand('doctrine:database:drop --force');
        self::runCommand('doctrine:database:create');
        self::runCommand('doctrine:schema:create');
        self::runCommand('fom:user:resetroot --username=root --password=root --email=root@example.com --silent');
        self::runCommand('doctrine:fixtures:load --fixtures=./mapbender/src/Mapbender/CoreBundle/DataFixtures/ORM/Epsg/ --append');
    }

    /**
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function getSharedClient()
    {
        static $client = null;
        return !$client ? $client = static::createClient() : $client;
    }

    /**
     * @return Mapbender
     */
    protected function getCore()
    {
        return $this->getContainer()->get("mapbender");
    }

    /**
     * @return null|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return $this->getSharedClient()->getContainer();
    }
}