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
    public function update(SourceInstance $instance, SourceItem $wmtslayersource)
    {
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
