<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Entity\SourceInstance;

interface SourceInstanceInformationInterface
{
    public function isInstanceEnabled(SourceInstance $sourceInstance): bool;
}
