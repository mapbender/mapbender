<?php


namespace Mapbender\CoreBundle\Component\Source;


use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;

interface SourceInstanceInformationInterface
{
    public function isInstanceEnabled(SourceInstance $sourceInstance);

    public function canDeactivateLayer(SourceInstanceItem $layer);
}
