<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

//use Doctrine\Common\Collections\ArrayCollection;
//use Doctrine\ORM\PersistentCollection;
//use Mapbender\CoreBundle\Component\ReflectionHandler;
use Mapbender\CoreBundle\Component\Exception\NotUpdateableException;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{

    /**
     * @inheritdoc
     */
    public function create($persist = true)
    {
        
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if ($this->entity->getRootlayer()) {
            self::createHandler($this->container, $this->entity->getRootlayer())->save();
        }
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
    }

    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL, $persist = true)
    {
        $instance        = new WmsInstance();
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
        if ($this->entity->getRootlayer()) {
            self::createHandler($this->container, $this->entity->getRootlayer())->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
    }

    /**
     * @inheritdoc
     */
    public function update(Source $sourceNew)
    {
        $transaction = $this->container->get('doctrine')->getManager()->getConnection()->isTransactionActive();
        if (!$transaction) {
            $this->container->get('doctrine')->getManager()->getConnection()->beginTransaction();
        }
        $updater = new WmsUpdater($this->entity);
        /* Update source attributes */
        $mapper  = $updater->getMapper();
        foreach ($mapper as $propertyName => $properties) {
            if ($propertyName === 'layers' || $propertyName === 'keywords' ||
                $propertyName === 'id' || $propertyName === 'instances') {
                continue;
            } else {
                $getMeth = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::GETTER]);
                $value   = $getMeth->invoke($sourceNew);
                if (is_object($value)) {
                    $refMethod = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::TOSET]);
                    $valueNew  = clone $value;
                    $this->container->get('doctrine')->getManager()->detach($valueNew);
                    $refMethod->invoke($this->entity, $valueNew);
                } else {
                    $refMethod = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::TOSET]);
                    $refMethod->invoke($this->entity, $value);
                }
            }
        }
        $updater->updateKeywords($this->entity, $sourceNew, $this->container->get('doctrine')->getManager(),
            'Mapbender\WmsBundle\Entity\WmsSourceKeyword');

        $rootHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $rootHandler->update($sourceNew->getRootlayer());

        $this->updateInstances();

        if (!$transaction) {
            $this->container->get('doctrine')->getManager()->getConnection()->commit();
        }
    }

    private function updateInstances(){
        foreach($this->getInstances() as $instance){
            self::createHandler($this->container, $instance)->update();
        }
    }

    /**
     * @inheritdoc
     */
    public function getInstances()
    {
        $query    = $this->container->get('doctrine')->getManager()->createQuery(
            "SELECT i FROM MapbenderWmsBundle:WmsInstance i WHERE i.source=:sid");
        $query->setParameters(array("sid" => $this->entity->getId()));
        $instList = $query->getResult();
        return $instList;
    }
}