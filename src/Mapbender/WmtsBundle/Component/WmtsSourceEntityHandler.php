<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmtsBundle\Entity\WmtsSource;

/**
 * @property WmtsSource $entity
 *
 * @author Paul Schmidt
 */
class WmtsSourceEntityHandler extends SourceEntityHandler
{
    /**
     * @inheritdoc
     */
    public function update(Source $sourceNew)
    {
    }
}
