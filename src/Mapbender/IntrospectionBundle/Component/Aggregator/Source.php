<?php

namespace Mapbender\IntrospectionBundle\Component\Aggregator;


use Mapbender\IntrospectionBundle\Component\WorkingSet;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Collects relational information many sources <=> many applications from the source perspective.
 *
 */
class Source
{
    /** @var Relation\SourceToApplications[] */
    protected $relations;

    /** @var \Mapbender\CoreBundle\Entity\Source[] */
    protected $unusedSources;

    /**
     * @param Relation\SourceToApplications[] $relationBuckets
     * @param \Mapbender\CoreBundle\Entity\Source[] $unusedSources
     */
    protected function __construct($relationBuckets, $unusedSources)
    {
        $this->relations = $relationBuckets;
        $this->unusedSources = $unusedSources;
    }

    /**
     * @param WorkingSet $workingSet
     * @return static
     */
    public static function build(WorkingSet $workingSet)
    {
        /** @var \Mapbender\CoreBundle\Entity\Source[] $unusedSources */
        $unusedSources = array();
        /** @var Relation\SourceToApplications[] $relations */
        $relations = array(
        );
        foreach ($workingSet->getSources() as $source) {
            $sourceId = $source->getId();
            $unusedSources[$sourceId] = $source;
        }
        foreach ($workingSet->getApplications() as $applicationEntity) {
            foreach (static::getLayerSetInstances($applicationEntity) as $lsi) {
                $source = $lsi->getSource();
                $sourceId = $source->getId();
                if (!array_key_exists($sourceId, $relations)) {
                    $relations[$sourceId] = new Relation\SourceToApplications($source);
                }
                $relations[$sourceId]->addSourceInstanceApplicationPair($lsi, $applicationEntity);
                unset($unusedSources[$sourceId]);
            }
        }
        ksort($unusedSources);
        ksort($relations);
        return new static($relations, $unusedSources);
    }

    /**
     * @return Relation\SourceToApplications[]
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * @return \Mapbender\CoreBundle\Entity\Source[]
     */
    public function getUnusedSources()
    {
        return $this->unusedSources;
    }

    /**
     * @param \Mapbender\CoreBundle\Entity\Application $application
     * @return SourceInstance[]
     */
    protected static function getLayerSetInstances($application)
    {
        $rv = array();
        foreach ($application->getLayersets() as $layerset) {
            foreach ($layerset->getInstances() as $instance) {
                $rv[] = $instance;
            }
        }
        return $rv;
    }
}
