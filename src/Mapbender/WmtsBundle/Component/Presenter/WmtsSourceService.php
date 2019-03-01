<?php


namespace Mapbender\WmtsBundle\Component\Presenter;



use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;

class WmtsSourceService extends SourceService
{
    public function getInnerConfiguration(SourceInstance $sourceInstance)
    {
        /** @var WmtsInstance $sourceInstance */
        return $sourceInstance->getConfiguration();
    }
}
