<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Doctrine\Common\Persistence\ObjectManager;
use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

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
     * Creates a SourceInstanceItem
     *
     * @param WmsInstance|SourceInstance  $instance
     * @param WmsLayerSource|SourceItem $layerSource
     * @param int                       $num
     * @return WmsInstanceLayer
     * @deprecated for poor wording and unnecessary container dependency
     * @internal
     */
    public function create(SourceInstance $instance, SourceItem $layerSource, $num = 0)
    {
        $instanceLayer = $this->entity;
        $instanceLayer->populateFromSource($instance, $layerSource, $num);
        return $this->entity;
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
     * Persists the instance layer and all child layers, recursively
     *
     * @param ObjectManager $manager
     * @param WmsInstanceLayer $entity
     */
    private static function persistRecursive(ObjectManager $manager, WmsInstanceLayer $entity)
    {
        $manager->persist($entity);
        foreach ($entity->getSublayer() as $sublayer) {
            static::persistRecursive($manager, $sublayer);
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    private function isScheduledForRemoval($entity)
    {
        $mgr = $this->container->get('doctrine')->getManager();
        $uow = $mgr->getUnitOfWork();
        $prop = new \ReflectionProperty(get_class($uow), 'entityDeletions');
        $prop->setAccessible(true);
        $list = $prop->getValue($uow);
        $cls = $mgr->getClassMetadata(get_class($entity))->getName();
        foreach ($list as $obj) {
            if ($mgr->getClassMetadata(get_class($obj))->getName() === $cls
                && $obj->getId() === $entity->getId()
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function update(SourceInstance $instance, SourceItem $wmslayersource)
    {
        /** @var WmsInstance $instance */
        /** @var WmsLayerSource $wmslayersource */
        $manager = $this->container->get('doctrine')->getManager();
        /* remove instance layers for missed layer sources */
        $toRemove = array();
        foreach ($this->entity->getSublayer() as $wmsinstlayer) {
            /** @var WmsInstanceLayer $wmsinstlayer */
            if ($this->isScheduledForRemoval($wmsinstlayer->getSourceItem())) {
                $toRemove[] = $wmsinstlayer;
            }
        }
        foreach ($toRemove as $rem) {
            $this->entity->getSublayer()->removeElement($rem);
            $removeHandler = new WmsInstanceLayerEntityHandler($this->container, $rem);
            $removeHandler->remove();
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
                $this->persistRecursive($manager, $sublayerInstance);
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
        $this->save();
    }

    /**
     * Generates layerset configuration for frontend consumption, recursively.
     *
     * @param WmsInstanceLayer|null $entity uses bound entity if omitted
     *    HACK alert: this function cannot currently be unbound / made static because
     *                deep down, the URL generation via tunnel might require access
     *                to the container. The bound entity OTOH is used for nothing
     *                outside the (optional) initial substitution for an empty argument
     *
     * @return array
     */
    public function generateConfiguration(WmsInstanceLayer $entity = null)
    {
        $entity = $entity ?: $this->entity;
        if (!$entity->getActive()) {
            return array();
        } else {
            $children = array();
            foreach ($entity->getSublayer() as $sublayer) {
                /** @var WmsInstanceLayer $sublayer */
                $configurationTemp = $this->generateConfiguration($sublayer);
                if (count($configurationTemp) > 0) {
                    $children[] = $configurationTemp;
                }
            }
            $layerConf = $this->getConfiguration($entity);
            $configuration = array(
                "options" => $layerConf,
                "state" => array(
                    "visibility" => null,
                    "info" => null,
                    "outOfScale" => null,
                    "outOfBounds" => null
                ),
            );
            switch ($entity->getSourceInstance()->getLayerOrder()) {
                default:
                case WmsInstance::LAYER_ORDER_TOP_DOWN:
                    // do nothing
                    break;
                case WmsInstance::LAYER_ORDER_BOTTOM_UP:
                    $children = array_reverse($children);
                    break;
            }
            if ($children) {
                $configuration["children"] = $children;
            }
            return $configuration;
        }
    }

    /**
     * @param WmsInstanceLayer|null $entity uses bound entity if omitted
     *    HACK alert: this function cannot currently be unbound / made static because
     *                deep down, the URL generation via tunnel might require access
     *                to the container. The bound entity OTOH is used for nothing
     *                outside the (optional) initial substitution for an empty argument
     * @inheritdoc
     */
    public function getConfiguration(WmsInstanceLayer $entity = null)
    {
        $entity = $entity ?: $this->entity;
        $sourceItem = $entity->getSourceItem();
        $configuration = array(
            "id" => strval($entity->getId()),
            "priority" => $entity->getPriority(),
            "name" => $sourceItem->getName() !== null ?
                $sourceItem->getName() : "",
            "title" => $entity->getTitle(),
            "queryable" => $entity->getInfo(),
            "style" => $entity->getStyle(),
            "minScale" => $entity->getMinScale(true),
            "maxScale" => $entity->getMaxScale(true),
        );
        $srses = array();
        foreach ($sourceItem->getMergedBoundingBoxes() as $bbox) {
            $srses[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        $configuration['bbox'] = $srses;
        $legendConfig = $this->getLegendConfig($entity);
        if ($legendConfig) {
            $configuration["legend"] = $legendConfig;
        }

        $configuration["treeOptions"] = array(
            "info" => $entity->getInfo(),
            "selected" => $entity->getSelected(),
            "toggle" => $entity->getSublayer()->count() > 0 ? $entity->getToggle() : null,
            "allow" => array(
                "info" => $entity->getAllowinfo(),
                "selected" => $entity->getAllowselected(),
                "toggle" => $entity->getSublayer()->count() > 0 ? $entity->getAllowtoggle() : null,
                "reorder" => $entity->getAllowreorder(),
            )
        );
        return $configuration;
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

    /**
     * Get a legend url from the layer's styles
     *
     * @param WmsLayerSource $layerSource
     * @return string|null
     */
    public static function getLegendUrlFromStyles(WmsLayerSource $layerSource)
    {
        // scan styles for legend url entries backwards
        // some WMS services may not populate every style with a legend, so just checking the last
        // style for a legend is not enough
        // @todo: style node selection should follow configured style
        foreach (array_reverse($layerSource->getStyles()) as $style) {
            /** @var Style $style */
            $legendUrl = $style->getLegendUrl();
            if ($legendUrl) {
                return $legendUrl->getOnlineResource()->getHref();
            }
        }
        return null;
    }

    /**
     * @param WmsLayerSource $layerSource
     * @return string|null
     */
    public static function getLegendGraphicUrl(WmsLayerSource $layerSource)
    {
        /** @var WmsSource $source*/
        $source = $layerSource->getSource();
        $glg = $source->getGetLegendGraphic();
        $layerName = $layerSource->getName();

        if ($glg && $layerName) {
            $source = $layerSource->getSource();
            $url = $glg->getHttpGet();
            $formats = $glg->getFormats();
            $params = "service=WMS&request=GetLegendGraphic"
                . "&version=" . $source->getVersion()
                . "&layer=" . $layerName
                . (count($formats) > 0 ? "&format=" . $formats[0] : "")
                . "&sld_version=1.1.0";
            return Utils::getHttpUrl($url, $params);
        }
        return null;
    }

    /**
     * Return the client-facing configuration for a layer's legend
     *
     * @param WmsInstanceLayer $entity
     * @return array
     */
    public function getLegendConfig(WmsInstanceLayer $entity)
    {
        $styleLegendUrl = $this->getLegendUrlFromStyles($entity->getSourceItem());
        if (WmsSourceEntityHandler::useTunnel($entity->getSourceInstance()->getSource())) {
            /** @var InstanceTunnelService $tunnelService */
            $tunnelService = $this->container->get('mapbender.source.instancetunnel.service');
            $tunnel = $tunnelService->makeEndpoint($entity->getSourceInstance());
        } else {
            $tunnel = null;
        }
        $layerName = $entity->getSourceItem()->getName();
        if ($styleLegendUrl) {
            if ($tunnel) {
                // request via tunnel, see ApplicationController::instanceTunnelAction
                // instruct the tunnel action that the legend url should be plucked from styles
                $tunnelInputUrl = '?request=GetLegendGraphic&_glgmode=styles&layer=' . $layerName;
                $publicLegendUrl = $tunnel->generatePublicUrl($tunnelInputUrl);
            } else {
                $publicLegendUrl = $styleLegendUrl;
            }
            return array(
                "url"   => $publicLegendUrl,
            );
        } else {
            $glgLegendUrl = $this->getLegendGraphicUrl($entity->getSourceItem());
            if ($glgLegendUrl) {
                if ($tunnel) {
                    // request via tunnel, see ApplicationController::instanceTunnelAction
                    // instruct the tunnel action that the legend url should be plucked from GetLegendGraphic
                    $tunnelInputUrl = '?request=GetLegendGraphic&_glgmode=GetLegendGraphic';
                    $publicLegendUrl = $tunnel->generatePublicUrl($tunnelInputUrl);
                } else {
                    $publicLegendUrl = $glgLegendUrl;
                }
                return array(
                    // this entry in the emitted config is only evaluated by the legend element if configured with
                    // "generateLegendUrl": true
                    "graphic"   => $publicLegendUrl,
                );
            }
        }
        return array();
    }
}
