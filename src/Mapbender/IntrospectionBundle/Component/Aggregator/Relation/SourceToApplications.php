<?php


namespace Mapbender\IntrospectionBundle\Component\Aggregator\Relation;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;


/**
 * Pure data class modeling the relations of ONE Source entity to other objects.
 */
class SourceToApplications
{
    /** @var Source */
    protected $source;

    /** @var Application[][] */
    protected $applications;

    /** @var SourceInstance[][] */
    protected $sourceInstances;

    /** @var ApplicationToSources[][] */
    protected $appRelations;

    /** @var bool */
    protected $sorted = false;

    /**
     * @param Source $source
     */
    public function __construct($source)
    {
        $this->setSource($source);
        $this->initSourceInstances();
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param Source $source
     * @throws \InvalidArgumentException
     */
    public function setSource($source)
    {
        if (!$source || !($source instanceof Source)) {
            throw new \InvalidArgumentException("Not a source entity");
        }
        $this->source = $source;
    }

    /**
     * @return ApplicationToSources[]
     */
    public function getApplicationRelations()
    {
        $this->ensureSort();
        $published = array_values($this->appRelations['published']);
        $unpublished = array_values($this->appRelations['unpublished']);
        return array_merge($published, $unpublished);
    }

    /**
     * @return SourceInstance[]
     */
    public function getSourceInstances()
    {
        $this->ensureSort();
        $enabled = array_values($this->sourceInstances['enabled']);
        $disabled = array_values($this->sourceInstances['disabled']);
        return array_merge($enabled, $disabled);
    }

    /**
     * @param SourceInstance $instance
     * @param Application $application
     * @return bool if instance added (instances not related to bound source will be filtered and dropped)
     */
    public function addSourceInstanceApplicationPair(SourceInstance $instance, $application)
    {
        if ($instance->getSource()->getId() != $this->source->getId()) {
            return false;
        }
        if ($instance->getEnabled()) {
            $instGroupKey = 'enabled';
        } else {
            $instGroupKey = 'disabled';
        }
        if ($application->isPublished()) {
            $appGroupKey = 'published';
        } else {
            $appGroupKey = 'unpublished';
        }
        $appId = $application->getId();
        if (!array_key_exists($appId, $this->appRelations[$appGroupKey])) {
            $this->appRelations[$appGroupKey][$appId] = new ApplicationToSources($application);
        }
        $this->appRelations[$appGroupKey][$appId]->addSourceInstance($instance);
        $this->sourceInstances[$instGroupKey][$instance->getId()] = $instance;
        $this->applications[$appGroupKey][$application->getId()] = $application;
        $this->sorted = false;
        return true;
    }

    /**
     */
    protected function initSourceInstances()
    {
        $this->sourceInstances = array(
            'enabled' => array(),
            'disabled' => array(),
        );
        $this->applications = array(
            'enabled' => array(),
            'disabled'=> array(),
        );
        $this->appRelations = array(
            'published' => array(),
            'unpublished' => array(),
        );
    }

    protected function ensureSort()
    {
        if (!$this->sorted) {
            ksort($this->applications['enabled']);
            ksort($this->applications['disabled']);
            ksort($this->sourceInstances['enabled']);
            ksort($this->sourceInstances['disabled']);
            $this->sorted = true;
        }
    }
}
