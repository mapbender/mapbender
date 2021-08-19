<?php

namespace Mapbender\CoreBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application as CmdApplication;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;

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
        $isTestEnv = static::$kernel->getEnvironment() == "test";

        if ($isTestEnv) {
            $this->runCommand('doctrine:database:drop --force');
            $this->runCommand('doctrine:database:create');
            $this->runCommand('doctrine:schema:create');
            $this->runCommand('fom:user:resetroot --username=root --password=root --email=root@example.com --silent');
            $this->runCommand('mapbender:database:init');
        }
    }

    /** @var CmdApplication Command application */
    protected $application;

    /**
     * Get CMD application
     *
     * @return mixed
     */
    protected function getApplication()
    {
        if (!$this->application) {
            $this->application = new CmdApplication(static::$kernel);
            $this->application->setAutoExit(false);
        }
        return $this->application;
    }

    /**
     * @param $command
     * @return mixed
     */
    protected function runCommand($command)
    {
        $command     = sprintf('%s --quiet', $command);
        $application = $this->getApplication();
        return $application->run(new StringInput($command));
    }

    /**
     * @param array $options
     * @return \Symfony\Bundle\FrameworkBundle\Client
     */
    protected function getClient(array $options = array())
    {
        static $client = null;
        return $client ? $client : $client = static::createClient($options);
    }

    /**
     * @return null|\Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected function getContainer()
    {
        return static::$kernel->getContainer();
    }
}
