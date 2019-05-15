<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;

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

    /**
     * @inheritdoc
     */
    public function create()
    {
        $this->entity->setTitle($this->entity->getSource()->getTitle());
        $this->entity->setRoottitle($this->entity->getSource()->getTitle());
        $source = $this->entity->getSource();

        $this->entity->setWeight(-1);
        $allowInfo = null;
        foreach ($source->getLayers() as $layer) {
            $instLayer = new WmtsInstanceLayer();
            $instLayerHandler = new WmtsInstanceLayerEntityHandler($this->container, $instLayer);
            $instLayerHandler->create($this->entity, $layer);
            if ($instLayer->getInfoformat()) {
                $allowInfo = true;
            }
        }
        $this->entity->setAllowinfo($allowInfo)
            ->setInfo($allowInfo);
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
    }

}
