<?php

namespace Mapbender\CoreBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Console\Application as CmdApplication;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Console\Input\StringInput;

class TestBase extends WebTestCase
{
    protected KernelBrowser $client;
    protected ?CmdApplication $application = null;

    public function setUp(): void
    {
        $this->client = static::createClient();
    }


    protected function getApplication(): CmdApplication
    {
        if (!$this->application) {
            $this->application = new CmdApplication(static::$kernel);
            $this->application->setAutoExit(false);
        }
        return $this->application;
    }

    protected function runCommand(string $command): int
    {
        $command     = sprintf('%s --quiet', $command);
        $application = $this->getApplication();
        return $application->run(new StringInput($command));
    }

    protected function getClient(): KernelBrowser
    {
        return $this->client;
    }
}
