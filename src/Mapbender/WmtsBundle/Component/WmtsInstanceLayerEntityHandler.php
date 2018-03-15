<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceItem;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

/**
 * Description of WmsInstanceLayerEntityHandler
 *
 * @author Paul Schmidt
 */
class WmtsInstanceLayerEntityHandler extends SourceInstanceItemEntityHandler
{

    /**
     * @inheritdoc
     */
    public function create(SourceInstance $instance, SourceItem $wmtslayersource, $num = 0, $persist = true)
    {
        $this->entity->setSourceInstance($instance);
        $this->entity->setSourceItem($wmtslayersource);
        $this->entity->setTitle($wmtslayersource->getTitle());
        $this->entity->setFormat(ArrayUtil::getValueFromArray($wmtslayersource->getFormats(), null, 0));
        $this->entity->setInfoformat(ArrayUtil::getValueFromArray($wmtslayersource->getInfoformats(), null, 0));
//        // @TODO min max from scaleHint
//        $this->entity->setMinScale($wmslayersource->getScaleRecursive() !== null ?
//                $wmslayersource->getScaleRecursive()->getMin() : null);
//        $this->entity->setMaxScale($wmslayersource->getScaleRecursive() !== null ?
//                $wmslayersource->getScaleRecursive()->getMax() : null);
//        $queryable = $wmtslayersource->getQueryable();
        $this->entity->setInfo(Utils::getBool(count($wmtslayersource->getInfoformats())));
        $this->entity->setAllowinfo(Utils::getBool(count($wmtslayersource->getInfoformats())));
//        $this->entity->setPriority($num);
        $instance->addLayer($this->entity);

        if ($persist) {
            $this->container->get('doctrine')->getManager()->persist($this->entity);
            $this->container->get('doctrine')->getManager()->flush();
        }
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
    public function update(SourceInstance $instance, SourceItem $wmslayersource)
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
//                    $wmslayersourceSub,
//                    $wmslayersourceSub->getPriority()
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
     * @param array $configuration
     * @return array
     */
    public function generateConfiguration()
    {
        if ($this->entity->getActive() === true) {
            return array(
                "options" => $this->getConfiguration(),
                "state" => array(
                    "visibility" => null,
                    "info" => null,
                    "outOfScale" => null,
                    "outOfBounds" => null),);
        }
        return array();
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $sourceItem      = $this->entity->getSourceItem();
        $resourceUrl     = $sourceItem->getResourceUrl();
        $urlTemplateType = count($resourceUrl) > 0 ? $resourceUrl[0] : null;
        $configuration   = array(
            "id" => $this->entity->getId() ? strval($this->entity->getId())
                : strval($this->entity->getSourceInstance()->getId()),
            'url' => $urlTemplateType ? $urlTemplateType->getTemplate() : null,
            'format' => $urlTemplateType ? $urlTemplateType->getFormat() : null,
            "title" => $this->entity->getTitle(),
            "style" => $this->entity->getStyle(),
            "identifier" => $this->entity->getSourceItem()->getIdentifier(),
            "tilematrixset" => $this->entity->getTileMatrixSet(),
        );

        $srses  = array();
        $llbbox = $this->entity->getSourceItem()->getLatlonBounds();
        if ($llbbox !== null) {
            $srses[$llbbox->getSrs()] = array(
                floatval($llbbox->getMinx()),
                floatval($llbbox->getMiny()),
                floatval($llbbox->getMaxx()),
                floatval($llbbox->getMaxy())
            );
        }
        if ($this->entity->getSourceItem()->getBoundingBoxes()) {
            foreach ($this->entity->getSourceItem()->getBoundingBoxes() as $bbox) {
                $srses = array_merge(
                    $srses,
                    array($bbox->getSrs() => array(
                        floatval($bbox->getMinx()),
                        floatval($bbox->getMiny()),
                        floatval($bbox->getMaxx()),
                        floatval($bbox->getMaxy())
                        )
                    )
                );
            }
        }
        $configuration['bbox']        = $srses;
        if (count($this->entity->getSourceItem()->getStyles()) > 0) {
            foreach ($this->entity->getSourceItem()->getStyles() as $style) {
                if (!$this->entity->getStyle()
                    || ($this->entity->getStyle() && $this->entity->getStyle() === $style->getIdentifier())) {
                    if ($style->getLegendUrl()) {
                        $configuration["legend"] = array(
                            "url" => $style->getLegendUrl()->getHref(),
                            "format" => $style->getLegendUrl()->getFormat());
                    }
                    break;
                }
            }
        }
        $configuration["treeOptions"] = array(
            "info" => $this->entity->getInfo(),
            "selected" => $this->entity->getSelected(),
            "toggle" => $this->entity->getToggle(),
            "allow" => array(
                "info" => $this->entity->getAllowinfo(),
                "selected" => $this->entity->getAllowselected(),
                "toggle" => $this->entity->getAllowtoggle(),
                "reorder" => null,
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
}
