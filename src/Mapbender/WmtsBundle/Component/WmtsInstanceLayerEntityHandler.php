<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\CoreBundle\Utils\ArrayUtil;
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
        $instanceLayer->setFormat(ArrayUtil::getValueFromArray($wmtslayersource->getFormats(), null, 0));
        $instanceLayer->setInfoformat(ArrayUtil::getValueFromArray($wmtslayersource->getInfoformats(), null, 0));
        $instanceLayer->setInfo(Utils::getBool(count($wmtslayersource->getInfoformats())));
        $instanceLayer->setAllowinfo(Utils::getBool(count($wmtslayersource->getInfoformats())));
        $styles = $wmtslayersource->getStyles();
        $instanceLayer->setStyle($styles[0]->identifier);
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
     * Generates a configuration for layers
     *
     * @param WmtsInstanceLayer|null $entity
     * @return array
     */
    public function generateConfiguration(WmtsInstanceLayer $entity = null)
    {
        $entity = $entity ?: $this->entity;
        if ($entity->getActive() === true) {
            return array(
                "options" => $this->getConfiguration($entity),
                "state" => array(
                    "visibility" => null,
                    "info" => null,
                    "outOfScale" => null,
                    "outOfBounds" => null),);
        }
        return array();
    }

    /**
     * @param WmtsInstanceLayer|null $entity
     * @inheritdoc
     */
    public function getConfiguration(WmtsInstanceLayer $entity = null)
    {
        $entity = $entity ?: $this->entity;
        $sourceItem      = $entity->getSourceItem();
        $resourceUrl     = $sourceItem->getResourceUrl();
        $urlTemplateType = count($resourceUrl) > 0 ? $resourceUrl[0] : null;
        $configuration   = array(
            "id" => $entity->getId() ? strval($entity->getId())
                : strval($entity->getSourceInstance()->getId()),
            'url' => $urlTemplateType ? $urlTemplateType->getTemplate() : null,
            'format' => $urlTemplateType ? $urlTemplateType->getFormat() : null,
            "title" => $entity->getTitle(),
            "style" => $entity->getStyle(),
            "identifier" => $entity->getSourceItem()->getIdentifier(),
            "tilematrixset" => $entity->getTileMatrixSet(),
        );

        $srses = array();
        foreach ($sourceItem->getMergedBoundingBoxes() as $bbox) {
            $srses[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        $configuration['bbox']        = $srses;
        $legendConfig = $this->getLegendConfig($entity);
        if ($legendConfig) {
            $configuration["legend"] = $legendConfig;
        }

        $configuration["treeOptions"] = array(
            "info" => $entity->getInfo(),
            "selected" => $entity->getSelected(),
            "toggle" => $entity->getToggle(),
            "allow" => array(
                "info" => $entity->getAllowinfo(),
                "selected" => $entity->getAllowselected(),
                "toggle" => $entity->getAllowtoggle(),
                "reorder" => null,
            )
        );
        return $configuration;
    }

    /**
     * Return the client-facing configuration for a layer's legend
     *
     * @param WmtsInstanceLayer $entity
     * @return array
     */
    public function getLegendConfig(WmtsInstanceLayer $entity)
    {
        // @todo: tunnel support
        foreach ($entity->getSourceItem()->getStyles() as $style) {
            if (!$entity->getStyle() || $entity->getStyle() === $style->getIdentifier()) {
                if ($style->getLegendurl()) {
                    return array(
                        'url' => $style->getLegendurl()->getHref(),
                    );
                }
            }
        }
        return array();
    }
}
