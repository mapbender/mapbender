<?php

namespace Mapbender\CoreBundle\Tests;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
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
        foreach ($this->getYamlApplications() as $application) {
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
        $client = $this->getClient();
        foreach ($this->getYamlApplications() as $application) {
            if ($application->isPublished() && !$application->getYamlRoles()) {
                $slug = $application->getSlug();
                $client->request('GET', '/application/' . rawurlencode($slug));
                $response = $client->getResponse();
                $this->assertTrue($response->isSuccessful(), 'Tried accessing application ' . $slug);
            }
        }
    }

    /**
     * @return Application[]
     */
    protected function getYamlApplications()
    {
        /** @var ApplicationYAMLMapper $repository */
        $repository = $this->getContainer()->get('mapbender.application.yaml_entity_repository');
        return $repository->getApplications();
    }
}
