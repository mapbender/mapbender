<?php

namespace Mapbender\IntrospectionBundle\Component\Aggregator;

use Mapbender\IntrospectionBundle\Component\WorkingSet;

/**
 * Collects relational information many applications <=> many sources from the application perspective.
 *
 */
class Application extends Base
{
    /** @var Relation\ApplicationToSources[] */
    protected $relationBuckets;

    /**
     * @param Relation\ApplicationToSources[][] $applicationRelationBuckets
     * @param \Mapbender\CoreBundle\Entity\Source[] $unusedSources
     */
    protected function __construct($applicationRelationBuckets, $unusedSources)
    {
        $this->relationBuckets = $applicationRelationBuckets;
        parent::__construct($unusedSources);
    }

    /**
     * @param WorkingSet $workingSet
     * @return static
     */
    public static function build(WorkingSet $workingSet)
    {
        $unusedSources = array();
        foreach ($workingSet->getSources() as $source) {
            $sourceId = $source->getId();
            $unusedSources[$sourceId] = $source;
        }
        $buckets = [];
        foreach ($workingSet->getApplications() as $applicationEntity) {
            $relation = new Relation\ApplicationToSources($applicationEntity);
            foreach (static::getLayerSetInstances($applicationEntity) as $lsi) {
                $relation->addSourceInstance($lsi);
                unset($unusedSources[$lsi->getSource()->getId()]);
            }
            $buckets[$applicationEntity->getId()] = $relation;
        }
        ksort($unusedSources);
        ksort($buckets);
        return new static($buckets, $unusedSources);
    }

    /**
     * @param bool $published
     * @return Relation\ApplicationToSources[]
     */
    public function getRelations()
    {
        return $this->relationBuckets;
    }
}
