<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;

/**
 * Description of WmsSourceEntityHandler
 *
 * @author Paul Schmidt
 */
class WmsSourceEntityHandler extends SourceEntityHandler
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
    public function save()
    {
        $manager = $this->container->get('doctrine')->getManager();
        if ($this->entity->getRootlayer()) {
            self::createHandler($this->container, $this->entity->getRootlayer())->save();
        }
        $manager->persist($this->entity);
        $manager->persist($this->entity->getContact());
        foreach ($this->entity->getKeywords() as $kwd) {
            $manager->persist($kwd);
        }
    }

    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL)
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
//        $this->container->get('doctrine')->getManager()->flush();
    }

    /**
     * @inheritdoc
     */
    public function update(Source $sourceNew)
    {
        $manager = $this->container->get('doctrine')->getManager();
        $transaction = $manager->getConnection()->isTransactionActive();
        if (!$transaction) {
            $manager->getConnection()->beginTransaction();
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
                } elseif (isset($properties[EntityUtil::TOSET])) {
                    $refMethod = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::TOSET]);
                    $refMethod->invoke($this->entity, $value);
                }
            }
        }
        $updater->updateKeywords(
            $this->entity,
            $sourceNew,
            $this->container->get('doctrine')->getManager(),
            'Mapbender\WmsBundle\Entity\WmsSourceKeyword'
        );
        $manager->persist($this->entity->getContact());
        $rootHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $rootHandler->update($sourceNew->getRootlayer());

        $this->updateInstances();
        if (!$transaction) {
            $this->container->get('doctrine')->getManager()->getConnection()->commit();
        }
    }

    private function updateInstances()
    {
        foreach ($this->getInstances() as $instance) {
            self::createHandler($this->container, $instance)->update();
        }
    }

    /**
     * @inheritdoc
     */
    public function getInstances()
    {
        $query    = $this->container->get('doctrine')->getManager()->createQuery(
            "SELECT i FROM MapbenderWmsBundle:WmsInstance i WHERE i.source=:sid"
        );
        $query->setParameters(array("sid" => $this->entity->getId()));
        $instList = $query->getResult();
        return $instList;
    }
}
