<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{

    public function create($persist = true)
    {
        ;
    }

    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL, $persist = true)
    {
        $instance = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->setLayerset($layerset);
        $entityHandler = self::createHandler($this->container, $instance);
        $entityHandler->create();
        if ($this->entity->getLayerset()) {
            $num = 0;
            foreach ($this->entity->getLayerset()->getInstances() as $instance) {
                $instHandler = self::createHandler($this->container, $instance);
                $instHandler->getEntity()->setWeight($num);
                $instHandler->generateConfiguration();
                if ($persist) {
                    $this->container->get('doctrine')->getManager()->persist($instHandler->getEntity());
                    $this->container->get('doctrine')->getManager()->flush();
                }
                $num++;
            }
        }
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $layerHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $layerHandler->remove();
        $this->container->get('doctrine')->getManager()->remove($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
    }

}
