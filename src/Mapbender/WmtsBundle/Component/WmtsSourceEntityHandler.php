<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsSource;

/**
 * Description of WmtsSourceEntityHandler
 *
 * @property WmtsSource $entity
 *
 * @author Paul Schmidt
 */
class WmtsSourceEntityHandler extends SourceEntityHandler
{

    /**
     * @inheritdoc
     */
    public function create()
    {
    }

    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL)
    {
        $instance = new WmtsInstance();
        $instance->setSource($this->entity);
        $instanceHandler = new WmtsInstanceEntityHandler($this->container, $instance);
        $instanceHandler->create();
        if ($layerset) {
            $instance->setLayerset($layerset);
            $num = 0;
            foreach ($layerset->getInstances() as $instanceAtLayerset) {
                /** @var WmsInstance|WmtsInstance $instanceAtLayerset */
                $instanceAtLayerset->setWeight($num);
                $num++;
            }
        }
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function update(Source $sourceNew)
    {
    }

}
