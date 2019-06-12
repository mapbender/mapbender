<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Component\KeywordUpdater;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
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
     * Creates a new WmsInstance from the bound WmsSource entity
     *
     * @return WmsInstance
     */
    public function createInstance()
    {
        $instance = new WmsInstance();
        $instance->setSource($this->entity);
        $instance->populateFromSource($this->entity);
        /** @var TypeDirectoryService $directory */
        $directory = $this->container->get('mapbender.source.typedirectory.service');
        $directory->getSourceService($instance)->initializeInstance($instance);
        return $instance;
    }

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

        $rootUpdateHandler = new WmsLayerSourceEntityHandler($this->container, $target->getRootlayer());
        $rootUpdateHandler->updateLayer($target->getRootlayer(), $sourceNew->getRootlayer());

        KeywordUpdater::updateKeywords(
            $target,
            $sourceNew,
            $em,
            'Mapbender\WmsBundle\Entity\WmsSourceKeyword'
        );

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
}
