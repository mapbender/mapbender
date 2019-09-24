<?php

namespace Mapbender\CoreBundle\Tests;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\RegionProperties;

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
    public function testYamlApplicationStructure()
    {
        $applications = $this->getCore()->getYamlApplicationEntities();
        foreach ($applications as $application) {
            $this->assertTrue($application instanceof Application);

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
    public function testPublicYamlApplicationAccess()
    {
        $applications = $this->getCore()->getYamlApplicationEntities();
        $client = $this->getClient();
        foreach ($applications as $application) {
            if ($application->isPublished() && !$application->getYamlRoles()) {
                $slug = $application->getSlug();
                $client->request('GET', '/application/' . rawurlencode($slug));
                $response = $client->getResponse();
                $this->assertTrue($response->isSuccessful(), 'Tried accessing application ' . $slug);
            }
        }
    }
}
