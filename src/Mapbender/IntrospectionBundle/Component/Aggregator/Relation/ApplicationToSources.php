<?php


namespace Mapbender\IntrospectionBundle\Component\Aggregator\Relation;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;


/**
 * Pure data class modeling the relations of ONE Application entity to other objects.
 *
 */
class ApplicationToSources
{
    /** @var Application */
    protected $application;

    /** @var SourceToSourceInstances[][] */
    protected $sourceRelations;

    /** @var SourceInstance[][] */
    protected $sourceInstances;

    /** @var bool */
    protected $sorted = false;

    /**
     * @param Application $application
     */
    public function __construct($application)
    {
        $this->setApplication($application);
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->application;
    }

    /**
     * @return SourceToSourceInstances[]
     */
    public function getSourceRelations()
    {
        $this->ensureSort();
        $enabled = array_values($this->sourceRelations['enabled']);
        $disabled = array_values($this->sourceRelations['disabled']);
        return array_merge($enabled, $disabled);
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
     * @param Application $application
     * @throws \InvalidArgumentException
     */
    public function setApplication($application)
    {
        if (!$application || !($application instanceof Application)) {
            throw new \InvalidArgumentException("Not an application entity");
        }
        $this->application = $application;
        $this->setSourceInstances(null);
    }

    /**
     * @param SourceInstance $instance
     */
    public function addSourceInstance(SourceInstance $instance)
    {
        if ($instance->getEnabled()) {
            $groupKey = 'enabled';
        } else {
            $groupKey = 'disabled';
        }

        $this->sourceInstances[$groupKey][$instance->getId()] = $instance;
        $source = $instance->getSource();
        $sourceId = $source->getId();
        if (!array_key_exists($sourceId, $this->sourceRelations[$groupKey])) {
            $this->sourceRelations[$groupKey][$sourceId] = new SourceToSourceInstances($source);
        }
        $this->sourceRelations[$groupKey][$sourceId]->addSourceInstance($instance);

        $this->sorted = false;
    }

    /**
     * @param SourceInstance[]|null $instances
     */
    public function setSourceInstances(array $instances = null)
    {
        $instances = $instances ?: array();
        $this->sourceInstances = array(
            'enabled' => array(),
            'disabled' => array(),
        );
        $this->sourceRelations = array(
            'enabled' => array(),
            'disabled' => array(),
        );
        foreach ($instances as $instance) {
            $this->addSourceInstance($instance);
        }
    }

    protected function ensureSort()
    {
        if (!$this->sorted) {
            ksort($this->sourceInstances['enabled']);
            ksort($this->sourceInstances['disabled']);
            ksort($this->sourceRelations['enabled']);
            ksort($this->sourceRelations['disabled']);
            $this->sorted = true;
        }
    }
}
