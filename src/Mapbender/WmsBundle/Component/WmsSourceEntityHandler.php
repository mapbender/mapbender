<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

/**
 * @author Paul Schmidt
 *
 * @property WmsSource $entity
 */
class WmsSourceEntityHandler extends SourceEntityHandler
{
    /**
     * Update a source from a new source
     *
     * @param WmsSource|Source $sourceNew
     */
    public function update(Source $sourceNew)
    {
        $this->updateSource($this->entity, $sourceNew);
    }

    public function updateSource(WmsSource $target, WmsSource $sourceNew)
    {
        $em = $this->getEntityManager();
        $transaction = $em->getConnection()->isTransactionActive();
        if (!$transaction) {
            $em->getConnection()->beginTransaction();
        }
        $classMeta = $em->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $sourceNew, $classMeta, false);

        $contact = clone $sourceNew->getContact();
        $em->detach($contact);
        if ($target->getContact()) {
            $em->remove($target->getContact());
        }
        $target->setContact($contact);

        $this->updateLayer($target->getRootlayer(), $sourceNew->getRootlayer());

        $this->copyKeywords($target, $sourceNew, 'Mapbender\WmsBundle\Entity\WmsSourceKeyword');

        foreach ($target->getInstances() as $instance) {
            $this->updateInstance($instance);
            $application = $instance->getLayerset()->getApplication();
            $application->setUpdated(new \DateTime('now'));
            $em->persist($application);
            $em->persist($instance);
        }

        if (!$transaction) {
            $em->getConnection()->commit();
        }
    }

    /**
     * @param WmsLayerSource $target
     * @param WmsLayerSource $updatedLayer
     */
    private function updateLayer(WmsLayerSource $target, WmsLayerSource $updatedLayer)
    {
        $priorityOriginal = $target->getPriority();
        $em = $this->getEntityManager();
        $classMeta = $em->getClassMetadata(ClassUtils::getClass($target));
        EntityUtil::copyEntityFields($target, $updatedLayer, $classMeta, false);
        // restore original priority
        $target->setPriority($priorityOriginal);
        $this->copyKeywords($target, $updatedLayer, 'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword');

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

    /**
     * @param WmsLayerSource $toClone
     * @param WmsLayerSource $cloneParent
     * @return WmsLayerSource
     */
    private function cloneLayer(WmsLayerSource $toClone, WmsLayerSource $cloneParent)
    {
        $em = $this->getEntityManager();
        $cloned = clone $toClone;
        $em->detach($cloned);
        $cloned->setId(null);
        $cloned->setSource($cloneParent->getSource());
        $cloned->setParent($cloneParent);
        $cloned->setPriority($cloneParent->getPriority());
        $cloned->setKeywords(new ArrayCollection());
        $cloneParent->addSublayer($cloned);
        $this->copyKeywords($cloned, $toClone, 'Mapbender\WmsBundle\Entity\WmsLayerSourceKeyword');
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


    private function updateInstance(WmsInstance $instance)
    {
        $source = $instance->getSource();

        if ($getMapFormats = $source->getGetMap()->getFormats()) {
            if (!in_array($instance->getFormat(), $getMapFormats)) {
                $instance->setFormat($getMapFormats[0]);
            }
        } else {
            $instance->setFormat(null);
        }
        if ($source->getGetFeatureInfo() && $featureInfoFormats = $source->getGetFeatureInfo()->getFormats()) {
            if (!in_array($instance->getInfoformat(), $featureInfoFormats)) {
                $instance->setInfoformat($featureInfoFormats[0]);
            }
        } else {
            $instance->setInfoFormat(null);
        }
        if ($exceptionFormats = $source->getExceptionFormats()) {
            if (!in_array($instance->getExceptionformat(), $exceptionFormats)) {
                $instance->setExceptionformat($exceptionFormats[0]);
            }
        } else {
            $instance->setExceptionformat(null);
        }
        $this->updateInstanceDimensions($instance);
        $this->updateInstanceLayer($instance->getRootlayer());
    }

    private function updateInstanceLayer(WmsInstanceLayer $target)
    {
        $em = $this->getEntityManager();
        /* remove instance layers for missed layer sources */
        foreach ($target->getSublayer() as $wmsinstlayer) {
            if ($em->getUnitOfWork()->isScheduledForDelete($wmsinstlayer->getSourceItem())) {
                $target->getSublayer()->removeElement($wmsinstlayer);
                $em->remove($wmsinstlayer);
            }
        }
        $sourceItem = $target->getSourceItem();
        foreach ($sourceItem->getSublayer() as $wmslayersourceSub) {
            $layer = $this->findInstanceLayer($wmslayersourceSub, $target->getSublayer());
            if ($layer) {
                $this->updateInstanceLayer($layer);
            } else {
                $instance = $target->getSourceInstance();
                $sublayerInstance = new WmsInstanceLayer();
                $sublayerInstance->populateFromSource($instance, $wmslayersourceSub, $wmslayersourceSub->getPriority());
                $sublayerInstance->setParent($target);
                $instance->getLayers()->add($sublayerInstance);
                $target->getSublayer()->add($sublayerInstance);
                $em->persist($sublayerInstance);
            }
        }
        $target->setPriority($sourceItem->getPriority());
        $queryable = $sourceItem->getQueryable();
        if (!$queryable) {
            if ($queryable !== null) {
                $queryable = false;
            }
            $target->setInfo($queryable);
            $target->setAllowinfo($queryable);
        }
        if ($sourceItem->getSublayer()->count() > 0) {
            $target->setToggle(is_bool($target->getToggle()) ? $target->getToggle() : false);
            $alowtoggle = is_bool($target->getAllowtoggle()) ? $target->getAllowtoggle() : true;
            $target->setAllowtoggle($alowtoggle);
        } else {
            $target->setToggle(null);
            $target->setAllowtoggle(null);
        }
        $em->persist($target);
    }

    /**
     * Finds an instance layer, that is linked with a given wms source layer.
     *
     * @param WmsLayerSource $wmssourcelayer wms layer source
     * @param array $instancelayerList list of instance layers
     * @return WmsInstanceLayer|null the instance layer, otherwise null
     */
    private function findInstanceLayer(WmsLayerSource $wmssourcelayer, $instancelayerList)
    {
        foreach ($instancelayerList as $instancelayer) {
            if ($wmssourcelayer->getId() === $instancelayer->getSourceItem()->getId()) {
                return $instancelayer;
            }
        }
        return null;
    }

    /**
     * @param WmsInstance $instance
     */
    private function updateInstanceDimensions(WmsInstance $instance)
    {
        $dimensionsOld = $instance->getDimensions();
        $sourceDimensions = $instance->getSource()->dimensionInstancesFactory();
        $dimensions = array();
        foreach ($sourceDimensions as $sourceDimension) {
            $newDimension = null;
            foreach ($dimensionsOld as $oldDimension) {
                if ($sourceDimension->compare($oldDimension)) {
                    /* replace attribute values */
                    $oldDimension->setUnitSymbol($sourceDimension->getUnitSymbol());
                    $oldDimension->setNearestValue($sourceDimension->getNearestValue());
                    $oldDimension->setCurrent($sourceDimension->getCurrent());
                    $oldDimension->setMultipleValues($sourceDimension->getMultipleValues());
                    $newDimension = $oldDimension;
                    break;
                }
            }
            if (!$newDimension) {
                $newDimension = clone $sourceDimension;
            }
            $dimensions[] = $newDimension;
        }
        $instance->setDimensions($dimensions);
    }

    /**
     * @param ContainingKeyword $target
     * @param ContainingKeyword $source
     * @param string $keywordClass
     */
    private function copyKeywords(ContainingKeyword $target, ContainingKeyword $source, $keywordClass)
    {
        KeywordUpdater::updateKeywords($target, $source, $this->getEntityManager(), $keywordClass);
    }
}
