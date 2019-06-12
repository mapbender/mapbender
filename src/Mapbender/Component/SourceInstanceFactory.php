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
}
