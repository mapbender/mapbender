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
class ApplicationTest extends TestBase
{
    /**
     * Test getting applications
     */
    public function testApplicationComponent()
    {
        $applications = $this->getCore()->getApplicationEntities();
        foreach ($applications as $application) {

            $this->assertTrue($application instanceof Application);

            // Check ID if YAML or Databases
            $this->assertTrue(
                $application->isDbBased() && $application->getId() > 0
                || $application->isYamlBased() && !$application->getId()
            );

            foreach ($application->getRegionProperties() as $regionProperty) {
                $this->assertTrue($regionProperty instanceof RegionProperties);
            }
        }
    }

    public function testLoginForm()
    {
        $client = $this->getSharedClient()->request('GET', '/user/login');
        $this->assertTrue($client->filter('html:contains("Login")')->count() > 0);
    }

    public function testMapbenderUserAppAbility()
    {
        $client   = $this->getSharedClient();
        $crawler  = $client->request('GET', '/application/mapbender_user');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
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