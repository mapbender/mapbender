<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\WmtsBundle\Entity\WmtsInstance;

/**
 * Description of WmtsSourceHandler
 * @property WmtsInstance $entity
 *
 * @author Paul Schmidt
 */
class WmtsInstanceEntityHandler extends SourceInstanceEntityHandler
{
    /**
     * @param array $configuration
     */
    public function setParameters(array $configuration = array())
    {
        throw new \Exception('not implemented yet');
    }
}
