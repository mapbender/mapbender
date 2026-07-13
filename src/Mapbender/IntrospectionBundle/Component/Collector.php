<?php


namespace Mapbender\IntrospectionBundle\Component;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;

class Collector
{
    /** @var WorkingSet|null */
    protected $defaultWorkingSet;

    public function __construct(protected ManagerRegistry $managerRegistry)
    {
    }

    /**
     * @param WorkingSet|null $workingSet
     * @return Aggregator\Application
     */
    public function collectApplicationInfo(WorkingSet $workingSet = null)
    {
        if (!$workingSet) {
            $workingSet = $this->getDefaultWorkingSet();
        }

        return Aggregator\Application::build($workingSet);
    }

    /**
     * @param WorkingSet|null $workingSet
     * @return Aggregator\Source
     */
    public function collectSourceInfo(WorkingSet $workingSet = null)
    {
        if (!$workingSet) {
            $workingSet = $this->getDefaultWorkingSet();
        }

        return Aggregator\Source::build($workingSet);
    }

    /**
     * @return WorkingSet
     */
    public function getDefaultWorkingSet()
    {
        if (!$this->defaultWorkingSet) {
            $this->defaultWorkingSet = new WorkingSet();
            $this->defaultWorkingSet->setApplications($this->getApplications());
            $this->defaultWorkingSet->setSources($this->getSources());
        }
        return $this->defaultWorkingSet;
    }

    /**
     * @param $name
     * @return ObjectRepository
     */
    protected function getEntityRepository(string $name): ObjectRepository
    {
        return $this->managerRegistry->getRepository($name);
    }

    /**
     * @return WmsSource[]
     */
    protected function getSources(): array
    {
        return $this->getEntityRepository(Source::class)->findAll();
    }

    /**
     * @return WmsInstance[]
     */
    protected function getSourceInstances(): array
    {
        return $this->getEntityRepository(SourceInstance::class)->findAll();
    }

    /**
     * @return Application[]
     */
    protected function getApplications(): array
    {
        return $this->getEntityRepository(Application::class)->findAll();
    }

    /**
     * @return Layerset[]
     */
    protected function getLayersets(): array
    {
        return $this->getEntityRepository(Layerset::class)->findAll();
    }
}
