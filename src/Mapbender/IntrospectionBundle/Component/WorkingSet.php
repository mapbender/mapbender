<?php


namespace Mapbender\IntrospectionBundle\Component;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;

/**
 * Set of objects to consider in various inspections.
 *
 * @todo: add elements
 *
 */
class WorkingSet
{
    /** @var Application[] */
    protected $applications;

    /** @var  Source[] */
    protected $sources;

    public function __construct(array $applications = null, array $sources = null)
    {
        $this->setApplications($applications);
        $this->setSources($sources);
    }

    /**
     * @return Application[]
     */
    public function getApplications()
    {
        return $this->applications;
    }

    /**
     * @return Source[]
     */
    public function getSources()
    {
        return $this->sources;
    }

    /**
     * @param Application[]|null $applications
     */
    public function setApplications(array $applications = null)
    {
        $this->applications = $applications ?: array();
    }

    /**
     * @param Application $application
     */
    public function addApplication(Application $application)
    {
        $this->applications[] = $application;
    }

    /**
     * @param Source $source
     */
    public function addSource(Source $source)
    {
        $this->sources[] = $source;
    }

    /**
     * @param Source[]|null $sources
     */
    public function setSources(array $sources = null)
    {
        $this->sources = $sources ?: array();
    }
}
