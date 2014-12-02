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
    
    public function create()
    {

    }
    
    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset)
    {
        $instance = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->setLayerset($layerset);
        $entityHandler = self::createHandler($this->container, $instance);
        $entityHandler->create();
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
        $this->entity->setGetMap($source->getGetMap());
        
        $rootHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $rootHandler->updateFromSourceItem($source->getRootlayer());
//        $this->refreshLayer($this->entity->getLayers()->get(0), $wmsFresh->getLayers()->get(0));
    }

    /**
     * @inheritdoc
     */
    public function updateInstances()
    {
        // TODO
    }

}
