<?php

namespace Mapbender\IntrospectionBundle\Component\Aggregator;


use Mapbender\IntrospectionBundle\Component\WorkingSet;

/**
 * Collects relational information many sources <=> many applications from the source perspective.
 *
 */
class Source extends Base
{
    /** @var Relation\SourceToApplications[] */
    protected $relations;

    /**
     * @param Relation\SourceToApplications[] $relationBuckets
     * @param \Mapbender\CoreBundle\Entity\Source[] $unusedSources
     */
    protected function __construct($relationBuckets, $unusedSources)
    {
        $this->relations = $relationBuckets;
        parent::__construct($unusedSources);
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
}
