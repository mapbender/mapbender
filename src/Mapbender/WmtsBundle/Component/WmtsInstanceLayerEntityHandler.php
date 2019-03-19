<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmtsBundle\Component\Presenter\WmtsSourceService;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;

/**
 * Description of WmtsInstanceLayerEntityHandler
 *
 * @property WmtsInstanceLayer $entity
 *
 * @author Paul Schmidt
 */
class WmtsInstanceLayerEntityHandler extends SourceInstanceItemEntityHandler
{

    /**
     * @param WmtsInstance $instance
     * @param WmtsLayerSource $wmtslayersource
     * @inheritdoc
     */
    public function create(SourceInstance $instance, SourceItem $wmtslayersource, $num = 0)
    {
        $instanceLayer = $this->entity;

        $instanceLayer->setSourceInstance($instance);
        $instanceLayer->setSourceItem($wmtslayersource);
        $instanceLayer->setTitle($wmtslayersource->getTitle());
        $instanceLayer->setInfoformat(ArrayUtil::getValueFromArray($wmtslayersource->getInfoformats(), null, 0));
        $instanceLayer->setInfo(Utils::getBool(count($wmtslayersource->getInfoformats())));
        $instanceLayer->setAllowinfo(Utils::getBool(count($wmtslayersource->getInfoformats())));
        $styles = $wmtslayersource->getStyles();
        if ($styles && count($styles)) {
            $instanceLayer->setStyle($styles[0]->identifier);
        }
        $instance->addLayer($instanceLayer);
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function update(SourceInstance $instance, SourceItem $wmtslayersource)
    {
        /* remove instance layers for missed layer sources */
//        foreach ($this->entity->getSublayer() as $wmsinstlayer) {
//            if (!$wmsinstlayer->getSourceItem()) {
//                self::createHandler($this->container, $wmsinstlayer)->remove();
//            }
//        }
//
//        foreach ($wmslayersource->getSublayer() as $wmslayersourceSub) {
//            $layer = $this->findLayer($wmslayersourceSub, $this->entity->getSublayer());
//            if ($layer) {
//                self::createHandler($this->container, $layer)->update($instance, $wmslayersourceSub);
//            } else {
//                self::createHandler($this->container, new WmsInstanceLayer())->create(
//                    $instance,
//                    $wmslayersourceSub
//                );
//            }
//        }
//        $this->entity->setPriority($wmslayersource->getPriority());
//        $origMinMax  = $wmslayersource->getScaleRecursive();
//        $scaleMinMax = null;
//        if ($origMinMax) {
//            $scaleMinMax = MinMax::create(
//                $origMinMax->getInRange($this->entity->getMinScale()),
//                $origMinMax->getInRange($this->entity->getMaxScale())
//            );
//        } else {
//            $scaleMinMax = MinMax::create($this->entity->getMinScale(), $this->entity->getMaxScale());
//        }
//        $this->entity->setMinScale($scaleMinMax ? $scaleMinMax->getMin() : null);
//        $this->entity->setMaxScale($scaleMinMax ? $scaleMinMax->getMax() : null);
//        $queryable = Utils::getBool($wmslayersource->getQueryable());
//        $this->entity->setInfo($queryable === true ? $this->entity->getInfo() : $queryable);
//        $this->entity->setAllowinfo($queryable === true ? $this->entity->getInfo() : $queryable);
//        if ($wmslayersource->getSublayer()->count() > 0) {
//            $this->entity->setToggle(is_bool($this->entity->getToggle()) ? $this->entity->getToggle() : false);
//            $alowtoggle = is_bool($this->entity->getAllowtoggle()) ? $this->entity->getAllowtoggle() : true;
//            $this->entity->setAllowtoggle($alowtoggle);
//        } else {
//            $this->entity->setToggle(null);
//            $this->entity->setAllowtoggle(null);
//        }
    }

    /**
     * @param WmtsInstanceLayer|null $entity
     * @deprecated
     * @return array
     * @todo: remove remaining usages from WmcBundle
     */
    public function generateConfiguration(WmtsInstanceLayer $entity = null)
    {
        return $this->getService()->getSingleLayerConfig($entity ?: $this->entity);
    }

    /**
     * @param WmtsInstanceLayer|null $entity
     * @deprecated
     * @return array
     * @todo: remove remaining usages from WmcBundle
     */
    public function getConfiguration(WmtsInstanceLayer $entity = null)
    {
        $layerConfig = $this->getService()->getSingleLayerConfig($entity ?: $this->entity);
        return $layerConfig['options'];
    }

    /**
     * @return WmtsSourceService
     */
    protected function getService()
    {
        /** @var WmtsSourceService $service */
        $service = $this->container->get('mapbender.source.wmts.service');
        return $service;
    }
}
