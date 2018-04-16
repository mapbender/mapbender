<?php


namespace Mapbender\IntrospectionBundle\Component\Aggregator\Relation;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Pure data class modeling the relations of ONE Source entity to SourceInstance entities
 */
class SourceToSourceInstances
{
    /** @var Source */
    protected $source;

    /** @var SourceInstance[][] */
    protected $instances;

    /** @var bool */
    protected $sorted = false;

    /**
     * SourceToSourceInstances constructor.
     * @param Source $source
     */
    public function __construct(Source $source)
    {
        $this->setSource($source);
    }

    public function setSource(Source $source)
    {
        $this->source = $source;
        $this->setSourceInstances(null);
    }

    /**
     * @return Source
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @return SourceInstance[]
     */
    public function getSourceInstances()
    {
        $this->ensureSort();
        $enabled = array_values($this->instances['enabled']);
        $disabled = array_values($this->instances['disabled']);
        return array_merge($enabled, $disabled);
    }

    /**
     * @param SourceInstance[]|null $instances
     */
    public function setSourceInstances(array $instances = null)
    {
        $instances = $instances ?: array();
        $this->instances = array(
            'enabled' => array(),
            'disabled' => array(),
        );
        foreach ($instances as $instance) {
            $this->addSourceInstance($instance);
        }
    }

    public function addSourceInstance(SourceInstance $instance)
    {
        if ($instance->getEnabled()) {
            $groupKey = 'enabled';
        } else {
            $groupKey = 'disabled';
        }

        $this->instances[$groupKey][$instance->getId()] = $instance;
        $this->sorted = false;
    }

    protected function ensureSort()
    {
        if (!$this->sorted) {
            ksort($this->instances['enabled']);
            ksort($this->instances['disabled']);
            $this->sorted = true;
        }
    }
}
