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
use Mapbender\CoreBundle\Entity\Source;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{
    
    public function create($persist = true)
    {

    }
    
    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL, $persist = true)
    {
        $instance = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->setLayerset($layerset);
        $instanceHandler = self::createHandler($this->container, $instance);
        $instanceHandler->create();
        if ($instanceHandler->getEntity()->getLayerset()) {
            $num = 0;
            foreach ($instanceHandler->getEntity()->getLayerset()->getInstances() as $instanceAtLayerset) {
                $instHandler = self::createHandler($this->container, $instanceAtLayerset);
                $instHandler->getEntity()->setWeight($num);
                $instHandler->generateConfiguration();
                if ($persist) {
                    $this->container->get('doctrine')->getManager()->persist($instHandler->getEntity());
                    $this->container->get('doctrine')->getManager()->flush();
                }
                $num++;
            }
        }
        return $instanceHandler->getEntity();
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

    /**
     * @inheritdoc
     */
    public function updateFromSource(Source $source)
    {
        $this->entity->setTitle($source->getTitle());
        $this->entity->setName($source->getName());
        $this->entity->setVersion($source->getVersion());
        $this->entity->setDescription($source->getDescription());
        $this->entity->setOnlineResource($source->getOnlineResource());
        $this->entity->setExceptionFormats($source->getExceptionFormats());
        $this->entity->setFees($source->getFees());
        $this->entity->setAccessConstraints($source->getAccessConstraints());
        $this->entity->setGetCapabilities($source->getGetCapabilities());
        $this->entity->setGetFeatureInfo($source->getGetFeatureInfo());
        $this->entity->setGetLegendGraphic($source->getGetLegendGraphic());
        $this->entity->setGetStyles($source->getGetStyles());
        $this->entity->setGetMap($source->getGetMap());
        # TODO other attributes
        $rootHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $rootHandler->updateFromSourceItem($source->getRootlayer());
//        $this->refreshLayer($this->entity->getLayers()->get(0), $wmsFresh->getLayers()->get(0));
        foreach ($this->getInstances() as $instance) {
            $instHandler = self::createHandler($this->container, $instance);
            $instHandler->generateConfiguration();
        }
    }

    /**
     * @inheritdoc
     */
    public function getInstances()
    {
        $query = $this->container->get('doctrine')->getManager()->createQuery(
            "SELECT i FROM MapbenderWmsBundle:WmsInstance i WHERE i.source=:sid");
        $query->setParameters(array("sid" => $this->entity->getId()));
        $instList = $query->getResult();
        return $instList;
    }

}
