<?php

namespace Mapbender\IntrospectionBundle\Component\Aggregator;

use Mapbender\IntrospectionBundle\Component\WorkingSet;

/**
 * Collects relational information many applications <=> many sources from the application perspective.
 *
 */
class Application extends Base
{
    /** @var Relation\ApplicationToSources[][] */
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
        $buckets = array(
            'published' => array(),
            'unpublished' => array(),
        );
        foreach ($workingSet->getApplications() as $applicationEntity) {
            $relation = new Relation\ApplicationToSources($applicationEntity);
            foreach (static::getLayerSetInstances($applicationEntity) as $lsi) {
                $relation->addSourceInstance($lsi);
                unset($unusedSources[$lsi->getSource()->getId()]);
            }
            if ($applicationEntity->isPublished()) {
                $buckets['published'][$applicationEntity->getId()] = $relation;
            } else {
                $buckets['unpublished'][$applicationEntity->getId()] = $relation;
            }
        }
        ksort($unusedSources);
        ksort($buckets['published']);
        ksort($buckets['unpublished']);
        return new static($buckets, $unusedSources);
    }

    /**
     * @param bool $published
     * @return Relation\ApplicationToSources[]
     */
    public function getRelations($published = true)
    {
        if ($published) {
            return $this->relationBuckets['published'];
        } else {
            return $this->relationBuckets['unpublished'];
        }
    }
}
