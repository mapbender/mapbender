<?php


namespace Mapbender\IntrospectionBundle\Component;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Mapbender;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectRepository;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

class Collector
{
    /** @var ContainerInterface */
    protected $container;

    /** @var WorkingSet|null */
    protected $defaultWorkingSet;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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
        return $this->getDoctrine()->getRepository($name);
    }

    /**
     * @return Registry
     */
    protected function getDoctrine()
    {
        /** @var Registry $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        return $doctrine;
    }

    /**
     * @return WmsSource[]
     */
    protected function getSources()
    {
        return $this->getEntityRepository('MapbenderWmsBundle:WmsSource')->findAll();
    }

    /**
     * @return WmsInstance[]
     */
    protected function getSourceInstances()
    {
        return $this->getEntityRepository('MapbenderWmsBundle:WmsInstance')->findAll();
    }

    /**
     * @return Application[]
     */
    protected function getApplications()
    {
        return $this->getMapbender()->getApplicationEntities();
    }

    /**
     * @return Layerset[]
     */
    protected function getLayersets()
    {
        return $this->getEntityRepository('MapbenderCoreBundle:Layerset')->findAll();
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer()
    {
        return $this->container;
    }

    /**
     * @return Mapbender
     */
    protected function getMapbender()
    {
        /** @var Mapbender $mapbenderService */
        $mapbenderService = $this->getContainer()->get('mapbender');
        return $mapbenderService;
    }
}
