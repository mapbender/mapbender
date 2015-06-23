<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Exception\NotUpdateableException;
use Mapbender\CoreBundle\Component\SourceItemEntityHandler;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\CoreBundle\Entity\SourceItem;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsLayerSourceEntityHandler extends SourceItemEntityHandler
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
        foreach ($this->entity->getSublayer() as $sublayer) {
            self::createHandler($this->container, $sublayer)->save();
        }
        foreach ($this->entity->getKeywords() as $kwd) {
            $this->container->get('doctrine')->getManager()->persist($kwd);
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getKeywords() as $kwd) {
            $this->container->get('doctrine')->getManager()->remove($kwd);
        }
        $this->removeRecursively($this->entity);
    }

    /**
     * Recursively remove a nested Layerstructure
     * @param WmsLayerSource
     * @param EntityManager
     */
    private function removeRecursively(WmsLayerSource $wmslayer)
    {
        foreach ($wmslayer->getSublayer() as $sublayer) {
            $this->removeRecursively($sublayer);
        }
        $this->container->get('doctrine')->getManager()->remove($wmslayer);
    }

    public function update(SourceItem $itemNew, WmsLayerUpdater $updater = null)
    {
        $manager = $this->container->get('doctrine')->getManager();
        $updater = $updater ? $updater : new WmsLayerUpdater($this->entity);
        $mapper  = $updater->getMapper();
        /* handle simple properties */
        foreach ($mapper as $propertyName => $properties) {
            if ($propertyName === 'sublayer' || $propertyName === 'id' || $propertyName === 'parent' ||
                $propertyName === 'source' || $propertyName === 'keywords') {
                continue;
            } else {
                $getter    = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::GETTER]);
                $value     = $getter->invoke($itemNew);
                $refMethod = new \ReflectionMethod($updater->getClass(), $properties[EntityUtil::TOSET]);
                if (is_object($value)) {
                    $valueNew = clone $value;
                    $this->container->get('doctrine')->getManager()->detach($valueNew);
                    $refMethod->invoke($this->entity, $valueNew);
                }
                $refMethod->invoke($this->entity, $value);
            }
        }
        /* handle sublayer- layer. Name is a unique identifier for a wms layer. */
        /* remove missed layers */
        $toRemove = array();
        foreach ($this->entity->getSublayer() as $layerOldSub) {
            $layerSublayer = $updater->findLayer($layerOldSub, $itemNew->getSublayer());
            if (count($layerSublayer) !== 1) {
                $toRemove[] = $layerOldSub;
            }
        }

        foreach($toRemove as $lay) {
            $this->entity->getSublayer()->removeElement($lay);
            self::createHandler($this->container, $lay)->remove();
        }

        /* update founded layers, add new layers */
        foreach ($itemNew->getSublayer() as $subItemNew) {
            $subItemsOld = $updater->findLayer($subItemNew, $this->entity->getSublayer());
            if (count($subItemsOld) === 0) { # add a new layer
                $lay = $updater->cloneLayer(
                    $this->entity->getSource(),
                    $subItemNew,
                    $this->container->get('doctrine')->getManager(),
                    $this->entity
                );
                $manager->persist($lay);
                $this->entity->addSubLayer($lay);
            } elseif (count($subItemsOld) === 1) { # update a layer
                $subItemsOld[0]->setPriority($subItemNew->getPriority());
                self::createHandler($this->container, $subItemsOld[0])->update($subItemNew, $updater);
            } else { # remove all old layers and add a new layer
                foreach ($subItemsOld as $layerToRemove) {
                    self::createHandler($this->container, $layerToRemove)->remove();
                }
                $lay = $updater->cloneLayer(
                    $this->entity->getSource(),
                    $subItemNew,
                    $this->container->get('doctrine')->getManager(),
                    $this->entity
                );
                $manager->persist($lay);
                $this->entity->addSubLayer($lay);
            }
        }

        $manager = $this->container->get('doctrine')->getManager();
        /* handle keywords */
        $updater->updateKeywords(
            $this->entity,
            $itemNew,
            $manager,
            'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword'
        );
        $manager->persist($this->entity);
    }
}
