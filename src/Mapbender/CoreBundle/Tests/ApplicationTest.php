<?php

namespace Mapbender\CoreBundle\Tests;

use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\RegionProperties;
use PHPUnit\Framework\Attributes\Group;

class ApplicationTest extends TestBase
{
    #[Group("unit")]
    #[Group("dataIntegrity")]
    public function testYamlApplicationStructure()
    {
        foreach ($this->getYamlApplications() as $application) {
            $this->assertTrue($application instanceof Application);

            foreach ($application->getRegionProperties() as $regionProperty) {
                $this->assertTrue($regionProperty instanceof RegionProperties);
            }
        }
    }

    #[Group("functional")]
    public function testLoginForm()
    {
        $client = $this->getClient()->request('GET', '/user/login');
        $this->assertTrue($client->filterXPath('//*[contains(text(), "Login")]')->count() > 0);
    }

    #[Group("functional")]
    public function testPublicYamlApplicationAccess()
    {
        $client = $this->getClient();
        foreach ($this->getYamlApplications() as $application) {
            if (!$application->isPublished()) continue;
            $yamlRoles = $application->getYamlRoles();
            if (empty($yamlRoles) || (count($yamlRoles) === 1 && $yamlRoles[0] === 'IS_AUTHENTICATED_ANONYMOUSLY')) {
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
    protected function getYamlApplications(): array
    {
        /** @var ApplicationYAMLMapper $repository */
        $repository = $this->getContainer()->get('mapbender.application.yaml_entity_repository');
        return $repository->getApplications();
    }
}
