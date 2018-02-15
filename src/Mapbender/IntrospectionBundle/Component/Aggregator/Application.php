<?php

namespace Mapbender\IntrospectionBundle\Component\Aggregator;

use Mapbender\IntrospectionBundle\Component\WorkingSet;
use Mapbender\CoreBundle\Entity\SourceInstance;

/**
 * Collects relational information many applications <=> many sources from the application perspective.
 *
 */
class Application
{
    /** @var Relation\ApplicationToSources[][] */
    protected $applicationInformation;

    /** @var \Mapbender\CoreBundle\Entity\Source[] */
    protected $unusedSources;

    /**
     * @param Relation\ApplicationToSources[][] $applicationRelationBuckets
     * @param \Mapbender\CoreBundle\Entity\Source[] $unusedSources
     */
    protected function __construct($applicationRelationBuckets, $unusedSources)
    {
        $this->applicationInformation = $applicationRelationBuckets;
        $this->unusedSources = $unusedSources;
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
            return $this->applicationInformation['published'];
        } else {
            return $this->applicationInformation['unpublished'];
        }
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
