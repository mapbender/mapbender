<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceItemEntityHandler;
use Mapbender\CoreBundle\Entity\SourceItem;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmtsLayerSourceEntityHandler extends SourceItemEntityHandler
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
        $this->container->get('doctrine')->getManager()->persist($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    public function update(SourceItem $itemNew)
    {
//        $updater = $updater ? $updater : new WmsLayerUpdater($this->entity);
//        $mapper  = $updater->getMapper();
//        /* handle simple properties */
//        foreach ($mapper as $propertyName => $properties) {
//            if ($propertyName === 'sublayer' || $propertyName === 'id' || $propertyName === 'parent' ||
//                $propertyName === 'source' || $propertyName === 'keywords') {
//                continue;
//            } else {
//                $getter    = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::GETTER]);
//                $value     = $getter->invoke($itemNew);
//                $refMethod = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::TOSET]);
//                if (is_object($value)) {
//                    $valueNew = clone $value;
//                    $this->container->get('doctrine')->getManager()->detach($valueNew);
//                    $refMethod->invoke($this->entity, $valueNew);
//                }
//                $refMethod->invoke($this->entity, $value);
//            }
//        }
//        /* handle sublayer- layer. Name is a unique identifier for a wms layer. */
//        /* remove missed layers */
//        foreach ($this->entity->getSublayer() as $layerOldSub) {
//            $layerSublayer = $updater->findLayer($layerOldSub, $itemNew->getSublayer());
//            if (count($layerSublayer) !== 1) {
//                self::createHandler($this->container, $layerOldSub)->remove();
//            }
//        }
//        /* update founded layers, add new layers */
//        foreach ($itemNew->getSublayer() as $subItemNew) {
//            $subItemsOld = $updater->findLayer($subItemNew, $this->entity->getSublayer());
//            if ($subItemNew->getName() === null) { # remove all old layers with name===null and add $subItemNew
//                foreach ($subItemsOld as $layerToRemove) {
//                    self::createHandler($this->container, $layerToRemove)->remove();
//                }
//                $this->save();
////                $this->container->get('doctrine')->getManager()->merge($this->entity);
//                $this->entity->addSubLayer(
//                    $updater->cloneLayer(
//                        $this->entity->getSource(),
//                        $subItemNew,
//                        $this->container->get('doctrine')->getManager(),
//                        $this->entity
//                    )
//                );
//                $this->save();
////                $this->container->get('doctrine')->getManager()->merge($this->entity);
//            } else {
//                if (count($subItemsOld) === 0) { # add a new layer
//                    $this->entity->addSubLayer(
//                        $updater->cloneLayer(
//                            $this->entity->getSource(),
//                            $subItemNew,
//                            $this->container->get('doctrine')->getManager(),
//                            $this->entity
//                        )
//                    );
//                    $this->save();
////                    $this->container->get('doctrine')->getManager()->merge($this->entity);
//                } elseif (count($subItemsOld) === 1) { # update a layer
//                    $subItemsOld[0]->setPriority($subItemNew->getPriority());
//                    self::createHandler($this->container, $subItemsOld[0])->update($subItemNew, $updater);
//                    $this->save();
//                } else { # remove all old layers and add a new layer
//                    foreach ($subItemsOld as $layerToRemove) {
//                        self::createHandler($this->container, $layerToRemove)->remove();
//                    }
//                    $this->save();
////                    $this->container->get('doctrine')->getManager()->merge($this->entity);
//                    $this->entity->addSubLayer(
//                        $updater->cloneLayer(
//                            $this->entity->getSource(),
//                            $subItemNew,
//                            $this->container->get('doctrine')->getManager(),
//                            $this->entity
//                        )
//                    );
//                    $this->save();
////                    $this->container->get('doctrine')->getManager()->merge($this->entity);
//                }
//            }
//        }
//
//        /* handle keywords */
//        $updater->updateKeywords(
//            $this->entity,
//            $itemNew,
//            $this->container->get('doctrine')->getManager(),
//            'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword'
//        );
//
//        $this->container->get('doctrine')->getManager()->persist($this->entity);
//        $this->container->get('doctrine')->getManager()->flush();
    }
}
