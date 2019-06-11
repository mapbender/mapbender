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
 * Description of WmsInstanceLayerEntityHandler
 *
 * @author Paul Schmidt
 *
 * @property WmsInstanceLayer $entity
 */
class WmsInstanceLayerEntityHandler extends SourceInstanceItemEntityHandler
{
    /**
     * @inheritdoc
     */
    public function update(SourceInstance $instance, SourceItem $wmslayersource)
    {
        /** @var WmsInstance $instance */
        /** @var WmsLayerSource $wmslayersource */
        $manager = $this->getEntityManager();
        /* remove instance layers for missed layer sources */
        $toRemove = array();
        foreach ($this->entity->getSublayer() as $wmsinstlayer) {
            if ($manager->getUnitOfWork()->isScheduledForDelete($wmsinstlayer->getSourceItem())) {
                $toRemove[] = $wmsinstlayer;
            }
        }
        foreach ($toRemove as $rem) {
            $this->entity->getSublayer()->removeElement($rem);
            $manager->remove($rem);
        }
        foreach ($wmslayersource->getSublayer() as $wmslayersourceSub) {
            $layer = $this->findLayer($wmslayersourceSub, $this->entity->getSublayer());
            if ($layer) {
                $layerInstanceHandler = new WmsInstanceLayerEntityHandler($this->container, $layer);
                $layerInstanceHandler->update($instance, $wmslayersourceSub);
            } else {
                $sublayerInstance = new WmsInstanceLayer();
                $sublayerInstance->populateFromSource($instance, $wmslayersourceSub, $wmslayersourceSub->getPriority());
                $sublayerInstance->setParent($this->entity);
                $instance->getLayers()->add($sublayerInstance);
                $this->entity->getSublayer()->add($sublayerInstance);
                $manager->persist($sublayerInstance);
            }
        }
        $this->entity->setPriority($wmslayersource->getPriority());
        $queryable = Utils::getBool($wmslayersource->getQueryable(), true);
        if ($queryable === '0') {
            $queryable = false;
        }
        if ($queryable === '1') {
            $queryable = true;
        }
        $this->entity->setInfo($queryable === true ? $this->entity->getInfo() : $queryable);
        $this->entity->setAllowinfo($queryable === true ? $this->entity->getInfo() : $queryable);
        if ($wmslayersource->getSublayer()->count() > 0) {
            $this->entity->setToggle(is_bool($this->entity->getToggle()) ? $this->entity->getToggle() : false);
            $alowtoggle = is_bool($this->entity->getAllowtoggle()) ? $this->entity->getAllowtoggle() : true;
            $this->entity->setAllowtoggle($alowtoggle);
        } else {
            $this->entity->setToggle(null);
            $this->entity->setAllowtoggle(null);
        }
        $manager->persist($this->entity);
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
