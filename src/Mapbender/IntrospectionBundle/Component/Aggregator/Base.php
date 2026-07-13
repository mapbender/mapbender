<?php


namespace Mapbender\IntrospectionBundle\Component\Aggregator;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstance;

abstract class Base
{
    /**
     * @param Source[] $unusedSources
     */
    protected function __construct(protected $unusedSources)
    {
    }

    /**
     * @return Source[]
     */
    public function getUnusedSources()
    {
        return $this->unusedSources;
    }

    /**
     * @param Application $application
     * @return SourceInstance[]
     */
    protected static function getLayerSetInstances($application)
    {
        $rv = [];
        foreach ($application->getLayersets() as $layerset) {
            foreach ($layerset->getInstances() as $instance) {
                $rv[] = $instance;
            }
        }
        return $rv;
    }
}
