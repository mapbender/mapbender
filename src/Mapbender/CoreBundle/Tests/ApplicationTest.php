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
     * @group unit
     * @group dataIntegrity
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

    /**
     * @group functional
     */
    public function testLoginForm()
    {
        $client = $this->getClient()->request('GET', '/user/login');
        $this->assertTrue($client->filter('html:contains("Login")')->count() > 0);
    }

    /**
     * @group functional
     */
    public function testMapbenderUserAppAbility()
    {
        $client   = $this->getClient();
        $crawler  = $client->request('GET', '/application/mapbender_user');
        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }
}