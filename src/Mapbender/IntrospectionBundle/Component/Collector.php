<?php


namespace Mapbender\IntrospectionBundle\Component;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;

class Collector
{
    /** @var ManagerRegistry */
    protected $managerRegistry;

    /** @var WorkingSet|null */
    protected $defaultWorkingSet;

    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
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
    protected function getEntityRepository($name)
    {
        return $this->managerRegistry->getRepository($name);
    }

    /**
     * @return WmsSource[]
     */
    protected function getSources()
    {
        return $this->getEntityRepository('MapbenderCoreBundle:Source')->findAll();
    }

    /**
     * @return WmsInstance[]
     */
    protected function getSourceInstances()
    {
        return $this->getEntityRepository('MapbenderCoreBundle:SourceInstance')->findAll();
    }

    /**
     * @return Application[]
     */
    protected function getApplications()
    {
        return $this->getEntityRepository('MapbenderCoreBundle:Application')->findAll();
    }

    /**
     * @return Layerset[]
     */
    protected function getLayersets()
    {
        return $this->getEntityRepository('MapbenderCoreBundle:Layerset')->findAll();
    }
}
