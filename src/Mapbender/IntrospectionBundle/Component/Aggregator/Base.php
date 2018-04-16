<?php


namespace Mapbender\IntrospectionBundle\Component\Aggregator;


use Mapbender\CoreBundle\Entity\SourceInstance;

abstract class Base
{
    /** @var \Mapbender\CoreBundle\Entity\Source[] */
    protected $unusedSources;

    /**
     * @param \Mapbender\CoreBundle\Entity\Source[] $unusedSources
     */
    protected function __construct($unusedSources)
    {
        $this->unusedSources = $unusedSources;
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
