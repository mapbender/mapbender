<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\SourceItemEntityHandler;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsLayerSourceEntityHandler extends SourceItemEntityHandler
{

    /** @var  WmsLayerSource */
    protected $entity;

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
        /** @var ObjectManager $manager */
        $manager = $this->container->get('doctrine')->getManager();
        $this->persistRecursive($manager, $this->entity);
    }

    /**
     * Persists the source layer and all child layers, recursively
     *
     * @param ObjectManager $manager
     * @param WmsLayerSource $entity
     */
    public static function persistRecursive(ObjectManager $manager, WmsLayerSource $entity)
    {
        $manager->persist($entity);
        foreach ($entity->getSublayer() as $sublayer) {
            static::persistRecursive($manager, $sublayer);
        }
        foreach ($entity->getKeywords() as $kwd) {
            $kwd->setReferenceObject($entity);
            $manager->persist($kwd);
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $this->removeRecursively($this->entity);
    }

    /**
     * Recursively remove a nested Layerstructure
     *
     * @param WmsLayerSource $wmslayer
     * @internal param $WmsLayerSource
     * @internal param $EntityManager
     */
    private function removeRecursively(WmsLayerSource $wmslayer)
    {
        /**
         * @todo: recursive remove is redundant wrt entity manager, but detaching from the relational collections
         *        may be necessary for update to work
         */
        foreach ($wmslayer->getSublayer() as $sublayer) {
            $this->removeRecursively($sublayer);
        }
        if ($wmslayer->getParent()) {
            $wmslayer->getParent()->getSublayer()->removeElement($wmslayer);
        }
        if ($wmslayer->getSource()) {
            $wmslayer->getSource()->getLayers()->removeElement($wmslayer);
        }
        $this->container->get('doctrine')->getManager()->remove($wmslayer);
    }

    /**
     * @inheritdoc
     */
    public function update(SourceItem $itemNew)
    {
        $prio = $this->entity->getPriority();
        $manager = $this->container->get('doctrine')->getManager();
        $classMeta = $manager->getClassMetadata(EntityUtil::getRealClass($this->entity));
        // set attribute values from $itemNew
        foreach ($classMeta->getFieldNames() as $fieldName) {
            if (!in_array($fieldName, $classMeta->getIdentifier())
                    && ($getter = EntityUtil::getReturnMethod($fieldName, $classMeta->getReflectionClass()))
                    && ($setter = EntityUtil::getSetMethod($fieldName, $classMeta->getReflectionClass()))) {
                $value     = $getter->invoke($itemNew);
                $setter->invoke($this->entity, is_object($value) ? clone $value : $value);
            }
            // ignore not identifier fields
        }
        /** @var WmsLayerSource $itemNew */

        $this->entity->setPriority($prio);
        KeywordUpdater::updateKeywords(
            $this->entity,
            $itemNew,
            $manager,
            'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword'
        );

        /* handle sublayer- layer. Name is a unique identifier for a wms layer. */
        /* remove missed layers */
        $toRemove = array();
        foreach ($this->entity->getSublayer() as $layerOldSub) {
            $layerSublayer = $this->findLayer($layerOldSub, $itemNew->getSublayer());
            if (count($layerSublayer) !== 1) {
                $toRemove[] = $layerOldSub;
            }
        }
        foreach ($toRemove as $lay) {
            $this->entity->getSublayer()->removeElement($lay);
            $this->removeRecursively($lay);
        }
        $num = 0;
        /* update founded layers, add new layers */
        foreach ($itemNew->getSublayer() as $subItemNew) {
            $num++;
            $subItemsOld = $this->findLayer($subItemNew, $this->entity->getSublayer());
            if (count($subItemsOld) === 0) { # add a new layer
                $lay = $this->cloneLayer(
                    $this->entity->getSource(),
                    $subItemNew,
                    $this->container->get('doctrine')->getManager(),
                    $this->entity
                );
                $lay->setPriority($prio + $num);
//                $manager->persist($lay);
//                $this->entity->addSublayer($lay);
//                $manager->persist($this->entity);
            } elseif (count($subItemsOld) === 1) { # update a layer
                $subItemsOld[0]->setPriority($prio + $num);
                $subLayerHandler = new WmsLayerSourceEntityHandler($this->container, $subItemsOld[0]);
                $subLayerHandler->update($subItemNew);
//                $manager->persist($this->entity);
            } else { # remove all old layers and add new layers
                foreach ($subItemsOld as $layerToRemove) {
                    $this->removeRecursively($layerToRemove);
                }
                $lay = $this->addLayer(
                    $this->entity->getSource(),
                    $subItemNew,
                    $this->container->get('doctrine')->getManager(),
                    $this->entity
                );
                $lay->setPriority($prio + $num);
//                $manager->persist($lay);
//                $this->entity->addSublayer($lay);
//                $manager->persist($this->entity);
            }
        }
//        $manager->persist($this->entity);
    }

    /**
     * Finds a layers at the layerlist.
     * @param WmsLayerSource $layer
     * @param WmsLayerSource[] $layerList
     * @return WmsLayerSource[]
     */
    private function findLayer($layer, $layerList)
    {
        $found = array();
        foreach ($layerList as $layerTmp) {
            if ($layer->getName() != null && $layer->getName() === $layerTmp->getName() ||
                ($layer->getName() == null && $layerTmp->getName() == null && $layer->getTitle() == $layerTmp->getTitle())) {
                $found[] = $layerTmp;
//                return $found;
            }
        }
        return $found;
    }

    private function cloneLayer(
        WmsSource $wms,
        WmsLayerSource $toClone,
        $entityManager,
        WmsLayerSource $parentForCloned = null
    ) {
        $cloned = clone $toClone;
        $entityManager->detach($cloned);
        $cloned->setId(null);
        $cloned->setKeywords(new ArrayCollection());
        $cloned->setParent($parentForCloned);
        $cloned->setPriority($parentForCloned !== null ? $parentForCloned->getPriority() : null);
        if ($parentForCloned !== null) {
            $parentForCloned->addSublayer($cloned);
        }
        $cloned->setSource($wms);
        $entityManager->persist($cloned);
        KeywordUpdater::updateKeywords(
            $cloned,
            $toClone,
            $entityManager,
            'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword'
        );
        if ($cloned->getSublayer()->count() > 0) {
            $children = new ArrayCollection();
            foreach ($cloned->getSublayer() as $subToClone) {
                $subCloned = $this->cloneLayer($wms, $subToClone, $entityManager, $cloned);
                $children->add($subCloned);
            }
            $cloned->setSublayer($children);
        }
        return $cloned;
    }



    private function addLayer(
        WmsSource $wms,
        WmsLayerSource $toClone,
        $entityManager,
        WmsLayerSource $parent = null
    ) {
        $cloned = clone $toClone;
        $entityManager->detach($cloned);
        $cloned->setId(null);
        $entityManager->persist($cloned);
        $cloned->setParent($parent);
        $cloned->setSource($wms);
        $cloned->setPriority($parent !== null ? $parent->getPriority() : null);
        $entityManager->persist($cloned);
        $parent->addSubLayer($parent);
        $entityManager->persist($cloned);
        $entityManager->persist($parent);
        $wms->addLayer($cloned);
        $entityManager->persist($wms);
        if ($cloned->getSublayer()->count() > 0) {
            $children = new ArrayCollection();
            foreach ($cloned->getSublayer() as $subToClone) {
                $subCloned = $this->addLayer($wms, $subToClone, $entityManager, $cloned);
                $children->add($subCloned);
            }
            $cloned->setSublayer($children);
        }
        return $cloned;
    }
}
