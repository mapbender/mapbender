<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\SourceItemEntityHandler;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 * @property WmsLayerSource $entity
 */
class WmsLayerSourceEntityHandler extends SourceItemEntityHandler
{

    /**
     * @inheritdoc
     * @param WmsLayerSource $itemNew
     * @deprecated
     */
    public function update(SourceItem $itemNew)
    {
        $this->updateLayer($this->entity, $itemNew);
    }

    /**
     * @param WmsLayerSource $target
     * @param WmsLayerSource $updatedLayer
     */
    public function updateLayer(WmsLayerSource $target, WmsLayerSource $updatedLayer)
    {
        $priorityOriginal = $target->getPriority();
        $em = $this->getEntityManager();
        $classMeta = $em->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $updatedLayer, $classMeta, false);
        // restore original priority
        $target->setPriority($priorityOriginal);
        $this->copyKeywords($target, $updatedLayer);

        /* handle sublayer- layer. Name is a unique identifier for a wms layer. */
        /* remove missed layers */
        $updatedSubLayers = $updatedLayer->getSublayer();
        $targetSubLayers = $target->getSublayer();
        foreach ($targetSubLayers as $layerOldSub) {
            $layerSublayer = $this->findLayer($layerOldSub, $updatedSubLayers);
            if (count($layerSublayer) !== 1) {
                $em->remove($layerOldSub);
            }
        }
        $num = 0;
        /* update founded layers, add new layers */
        foreach ($updatedSubLayers as $subItemNew) {
            $num++;
            $subItemsOld = $this->findLayer($subItemNew, $targetSubLayers);
            if (count($subItemsOld) === 1) {
                // update single layer
                $subItemsOld[0]->setPriority($priorityOriginal + $num);
                $this->updateLayer($subItemsOld[0], $subItemNew);
            } else {
                foreach ($subItemsOld as $layerToRemove) {
                    $em->remove($layerToRemove);
                }
                $lay = $this->cloneLayer($subItemNew, $target);
                $lay->setPriority($priorityOriginal + $num);
            }
        }
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
        $matchName = $layer->getName();
        $matchTitle = $layer->getTitle();

        foreach ($layerList as $candidate) {
            $namesMatch = $matchName && $matchName === $candidate->getName();
            $titlesMatch = $matchTitle && $matchTitle === $candidate->getTitle();
            if ($namesMatch || (!$matchName && $titlesMatch)) {
                $found[] = $candidate;
            }
        }
        return $found;
    }

    private function copyKeywords(WmsLayerSource $targetLayer, WmsLayerSource $sourceLayer)
    {
        $targetLayer->setKeywords(new ArrayCollection());
        KeywordUpdater::updateKeywords(
            $targetLayer,
            $sourceLayer,
            $this->getEntityManager(),
            'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword'
        );
    }

    private function cloneLayer(WmsLayerSource $toClone, WmsLayerSource $cloneParent)
    {
        $em = $this->getEntityManager();
        $cloned = clone $toClone;
        $em->detach($cloned);
        $cloned->setId(null);
        $cloned->setSource($cloneParent->getSource());
        $cloned->setParent($cloneParent);
        $cloned->setPriority($cloneParent->getPriority());
        $cloneParent->addSublayer($cloned);
        $this->copyKeywords($cloned, $toClone);
        $em->persist($cloned);
        if ($cloned->getSublayer()->count() > 0) {
            $children = new ArrayCollection();
            foreach ($cloned->getSublayer() as $subToClone) {
                $subCloned = $this->cloneLayer($subToClone, $cloned);
                $children->add($subCloned);
            }
            $cloned->setSublayer($children);
        }
        return $cloned;
    }
}
