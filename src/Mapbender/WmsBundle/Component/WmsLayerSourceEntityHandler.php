<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
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
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        foreach ($this->entity->getSublayer() as $sublayer) {
            self::createHandler($this->container, $sublayer)->save();
        }
        foreach ($this->entity->getKeywords() as $kwd) {
            $kwd->setReferenceObject($this->entity);
            $this->container->get('doctrine')->getManager()->persist($kwd);
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
        foreach ($wmslayer->getSublayer() as $sublayer) {
            $this->removeRecursively($sublayer);
        }
        foreach ($this->entity->getKeywords() as $kwd) {
            $this->container->get('doctrine')->getManager()->remove($kwd);
        }
        if ($this->entity->getParent()) {
            $this->entity->getParent()->getSublayer()->removeElement($this->entity);
        }
        if ($this->entity->getSource()) {
            $this->entity->getSource()->getLayers()->removeElement($wmslayer);
        }
        $this->container->get('doctrine')->getManager()->remove($wmslayer);
    }

    /**
     * @inheritdoc
     */
    public function update(SourceItem $itemNew)
    {# set priority
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
            self::createHandler($this->container, $lay)->remove();
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
                self::createHandler($this->container, $subItemsOld[0])->update($subItemNew);
//                $manager->persist($this->entity);
            } else { # remove all old layers and add new layers
                foreach ($subItemsOld as $layerToRemove) {
                    $this->entity->getSublayer()->removeElement($layerToRemove);
                    self::createHandler($this->container, $layerToRemove)->remove();
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
     * @param type $layer
     * @param type $layerList
     * @return array true
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
