<?php


namespace Mapbender\Component;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;

interface SourceInstanceFactory
{
    /**
     * @param Source $source
     * @return SourceInstance
     */
    public function createInstance(Source $source);

    /**
     * @param array $data
     * @param string $id used for instance and as instance layer id prefix
     * @return SourceInstance
     */
    public function fromConfig(array $data, $id);
}
