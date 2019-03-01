<?php


namespace Mapbender\WmtsBundle\Component\Presenter;



use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Component\WmtsInstanceEntityHandler;

class WmtsSourceService extends SourceService
{
    public function getInnerConfiguration(SourceInstance $sourceInstance)
    {
        $eh = new WmtsInstanceEntityHandler($this->container, $sourceInstance);
        return $eh->getConfiguration();
    }
}
