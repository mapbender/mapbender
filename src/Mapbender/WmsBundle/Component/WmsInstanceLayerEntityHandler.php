<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

/**
 * @author Paul Schmidt
 *
 * @property WmsInstanceLayer $entity
 */
class WmsInstanceLayerEntityHandler extends SourceInstanceItemEntityHandler
{
    /**
     * @inheritdoc
     * @param WmsInstance $instance
     * @param WmsLayerSource $wmslayersource
     */
    public function update(SourceInstance $instance, SourceItem $wmslayersource)
    {
        return $this->updateInstanceLayer($this->entity);
    }

    public function updateInstanceLayer(WmsInstanceLayer $target)
    {
        $em = $this->getEntityManager();
        $instance = $target->getSourceInstance();
        /* remove instance layers for missed layer sources */
        foreach ($target->getSublayer() as $wmsinstlayer) {
            if ($em->getUnitOfWork()->isScheduledForDelete($wmsinstlayer->getSourceItem())) {
                $target->getSublayer()->removeElement($wmsinstlayer);
                $em->remove($wmsinstlayer);
            }
        }
        $sourceItem = $target->getSourceItem();
        foreach ($sourceItem->getSublayer() as $wmslayersourceSub) {
            $layer = $this->findLayer($wmslayersourceSub, $target->getSublayer());
            if ($layer) {
                $this->updateInstanceLayer($layer);
            } else {
                $sublayerInstance = new WmsInstanceLayer();
                $sublayerInstance->populateFromSource($instance, $wmslayersourceSub, $wmslayersourceSub->getPriority());
                $sublayerInstance->setParent($target);
                $instance->getLayers()->add($sublayerInstance);
                $target->getSublayer()->add($sublayerInstance);
                $em->persist($sublayerInstance);
            }
        }
        $target->setPriority($sourceItem->getPriority());
        $queryable = Utils::getBool($sourceItem->getQueryable(), true);
        if ($queryable === '0') {
            $queryable = false;
        }
        if ($queryable === '1') {
            $queryable = true;
        }
        $target->setInfo($queryable === true ? $target->getInfo() : $queryable);
        $target->setAllowinfo($queryable === true ? $target->getInfo() : $queryable);
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
     * @return WmsInstanceLayer | null the instance layer, otherwise null
     */
    public function findLayer(WmsLayerSource $wmssourcelayer, $instancelayerList)
    {
        foreach ($instancelayerList as $instancelayer) {
            if ($wmssourcelayer->getId() === $instancelayer->getSourceItem()->getId()) {
                return $instancelayer;
            }
        }
        return null;
    }
}
