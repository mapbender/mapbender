<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Contact;
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
    public function save()
    {
        $entityManager = $this->getEntityManager();
        $entityManager->persist($this->entity);
        $cont = $this->entity->getContact();
        if ($cont == null) {
            $cont = new Contact();
            $this->entity->setContact($cont);
        }
        $this->container->get('doctrine')->getManager()->persist($cont);
        foreach ($this->entity->getLayers() as $layer) {
            self::createHandler($this->container, $layer)->save();
        }
        foreach ($this->entity->getThemes() as $theme) {
            self::createHandler($this->container, $theme)->save();
        }
        foreach ($this->entity->getTilematrixsets() as $tms) {
            $entityManager->persist($tms);
        }
        $entityManager->persist($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function createInstance(Layerset $layerset = NULL, $persist = true)
    {
        $instance = new WmtsInstance();
        $instance->setSource($this->entity);
        $instanceHandler = new WmtsInstanceEntityHandler($this->container, $instance);
        $instanceHandler->create();
        $entityManager = $this->getEntityManager();
        if ($layerset) {
            $instance->setLayerset($layerset);
            $num = 0;
            foreach ($layerset->getInstances() as $instanceAtLayerset) {
                /** @var WmsInstance|WmtsInstance $instanceAtLayerset */
                $instanceAtLayerset->setWeight($num);
                if ($persist) {
                    $this->container->get('doctrine')->getManager()->persist($instanceAtLayerset);
                }
                $num++;
            }
        }
        if ($persist) {
            foreach ($instance->getLayers() as $instanceLayer) {
                $entityManager->persist($instanceLayer);
            }
        }
        return $instance;
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getLayers() as $layer) {
            self::createHandler($this->container, $layer)->remove();
        }
        foreach ($this->entity->getThemes() as $theme) {
            self::createHandler($this->container, $theme)->remove();
        }
        $this->getEntityManager()->remove($this->entity);
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
//        $updater = new WmtsUpdater($this->entity);
//        /* Update source attributes */
//        $mapper  = $updater->getMapper();
//        foreach ($mapper as $propertyName => $properties) {
//            if ($propertyName === 'layers' || $propertyName === 'keywords' ||
//                $propertyName === 'id' || $propertyName === 'instances') {
//                continue;
//            } else {
//                $getMeth = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::GETTER]);
//                $value   = $getMeth->invoke($sourceNew);
//                if (is_object($value)) {
//                    $refMethod = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::TOSET]);
//                    $valueNew  = clone $value;
//                    $this->container->get('doctrine')->getManager()->detach($valueNew);
//                    $refMethod->invoke($this->entity, $valueNew);
//                } elseif(isset($properties[EntityUtil::TOSET])) {
//                    $refMethod = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::TOSET]);
//                    $refMethod->invoke($this->entity, $value);
//                }
//            }
//        }
//        $updater->updateKeywords(
//            $this->entity,
//            $sourceNew,
//            $this->container->get('doctrine')->getManager(),
//            'Mapbender\WmtsBundle\Entity\WmtsSourceKeyword'
//        );
//
//        $rootHandler = self::createHandler($this->container, $this->entity->getRootlayer());
//        $rootHandler->update($sourceNew->getRootlayer());
//
//        $this->updateInstances();

        if (!$transaction) {
            $this->container->get('doctrine')->getManager()->getConnection()->commit();
        }
    }

    private function updateInstances()
    {
//        foreach ($this->getInstances() as $instance) {
//            self::createHandler($this->container, $instance)->update();
//        }
    }

    /**
     * @inheritdoc
     */
    public function getInstances()
    {
        $query    = $this->container->get('doctrine')->getManager()->createQuery(
            "SELECT i FROM MapbenderWmtsBundle:WmtsInstance i WHERE i.source=:sid"
        );
        $query->setParameters(array("sid" => $this->entity->getId()));
        $instList = $query->getResult();
        return $instList;
    }
}
