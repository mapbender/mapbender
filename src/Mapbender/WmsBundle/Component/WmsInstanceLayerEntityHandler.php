<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceItemEntityHandler;
use Mapbender\CoreBundle\Component\SourceItem;
use Mapbender\CoreBundle\Component\Utils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsInstanceLayerEntityHandler extends SourceInstanceItemEntityHandler
{

    /**
     * @inheritdoc
     */
    public function create(SourceInstance $instance, SourceItem $wmslayersource, $num = 0, $persist = true)
    {
        $this->entity->setSourceInstance($instance);
        $this->entity->setSourceItem($wmslayersource);
        $this->entity->setTitle($wmslayersource->getTitle());
        // @TODO min max from scaleHint
        $this->entity->setMinScale($wmslayersource->getScaleRecursive() !== null ?
                $wmslayersource->getScaleRecursive()->getMin() : null);
        $this->entity->setMaxScale($wmslayersource->getScaleRecursive() !== null ?
                $wmslayersource->getScaleRecursive()->getMax() : null);
        $queryable = $wmslayersource->getQueryable();
        $this->entity->setInfo(Utils::getBool($queryable));
        $this->entity->setAllowinfo(Utils::getBool($queryable));
        $this->entity->setPriority($num);
        $instance->addLayer($this->entity);
        if ($wmslayersource->getSublayer()->count() > 0) {
            $this->entity->setToggle(false);
            $this->entity->setAllowtoggle(true);
        } else {
            $this->entity->setToggle(null);
            $this->entity->setAllowtoggle(null);
        }
        if ($persist) {
            $this->container->get('doctrine')->getManager()->persist($this->entity);
            $this->container->get('doctrine')->getManager()->flush();
        }
        foreach ($wmslayersource->getSublayer() as $wmslayersourceSub) {
            $entityHandler = self::createHandler($this->container, new WmsInstanceLayer());
            $entityHandler->create($instance, $wmslayersourceSub, $num + 1, $persist);
            $entityHandler->getEntity()->setParent($this->entity);
            $this->entity->addSublayer($entityHandler->getEntity());
            if ($persist) {
                $this->container->get('doctrine')->getManager()->persist($this->entity);
                $this->container->get('doctrine')->getManager()->persist($entityHandler->getEntity());
                $this->container->get('doctrine')->getManager()->flush();
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        foreach ($this->entity->getSublayer() as $sublayer) {
            self::createHandler($this->container, $sublayer)->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function update(SourceInstance $instance, SourceItem $wmslayersource)
    {
        /* remove instance layers for missed layer sources */
        foreach ($this->entity->getSublayer() as $wmsinstlayer) {
            if (!$wmsinstlayer->getSourceItem()) {
                self::createHandler($this->container, $wmsinstlayer)->remove();
            }
        }

        foreach ($wmslayersource->getSublayer() as $wmslayersourceSub) {
            $layer = $this->findLayer($wmslayersourceSub, $this->entity->getSublayer());
            if ($layer) {
                self::createHandler($this->container, $layer)->update($instance, $wmslayersourceSub);
            } else {
                self::createHandler($this->container, new WmsInstanceLayer())->create(
                    $instance,
                    $wmslayersourceSub,
                    $wmslayersourceSub->getPriority()
                );
            }
        }
        $this->entity->setPriority($wmslayersource->getPriority());
        $origMinMax  = $wmslayersource->getScaleRecursive();
        $scaleMinMax = null;
        if ($origMinMax) {
            $scaleMinMax = MinMax::create(
                $origMinMax->getInRange($this->entity->getMinScale()),
                $origMinMax->getInRange($this->entity->getMaxScale())
            );
        } else {
            $scaleMinMax = MinMax::create($this->entity->getMinScale(), $this->entity->getMaxScale());
        }
        $this->entity->setMinScale($scaleMinMax ? $scaleMinMax->getMin() : null);
        $this->entity->setMaxScale($scaleMinMax ? $scaleMinMax->getMax() : null);
        $queryable = Utils::getBool($wmslayersource->getQueryable());
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
    }

    /**
     * Generates a configuration for layers
     *
     * @param array $configuration
     * @return array
     */
    public function generateConfiguration()
    {
        $configuration = array();
        if ($this->entity->getActive() === true) {
            $children = array();
            if ($this->entity->getSublayer()->count() > 0) {
                foreach ($this->entity->getSublayer() as $sublayer) {
                    $instLayHandler    = self::createHandler($this->container, $sublayer);
                    $configurationTemp = $instLayHandler->generateConfiguration();
                    if (count($configurationTemp) > 0) {
                        $children[] = $configurationTemp;
                    }
                }
            }
            $layerConf     = $this->getConfiguration();
            $configuration = array(
                "options" => $layerConf,
                "state" => array(
                    "visibility" => null,
                    "info" => null,
                    "outOfScale" => null,
                    "outOfBounds" => null),);
            if (count($children) > 0) {
                $configuration["children"] = $children;
            }
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = array(
            "id" => strval($this->entity->getId()),
            "priority" => $this->entity->getPriority(),
            "name" => $this->entity->getSourceItem()->getName() !== null ? $this->entity->getSourceItem()->getName() : "",
            "title" => $this->entity->getTitle(),
            "queryable" => $this->entity->getInfo(),
            "style" => $this->entity->getStyle(),
            "minScale" => $this->entity->getMinScale() !== null ? floatval($this->entity->getMinScale()) : null,
            "maxScale" => $this->entity->getMaxScale() !== null ? floatval($this->entity->getMaxScale()) : null
        );
        $srses         = array();
        $llbbox        = $this->entity->getSourceItem()->getLatlonBounds();
        if ($llbbox !== null) {
            $srses[$llbbox->getSrs()] = array(
                floatval($llbbox->getMinx()),
                floatval($llbbox->getMiny()),
                floatval($llbbox->getMaxx()),
                floatval($llbbox->getMaxy())
            );
        }
        foreach ($this->entity->getSourceItem()->getBoundingBoxes() as $bbox) {
            $srses[$bbox->getSrs()] = array(
                floatval($bbox->getMinx()),
                floatval($bbox->getMiny()),
                floatval($bbox->getMaxx()),
                floatval($bbox->getMaxy()));
        }
        $configuration['bbox'] = $srses;
        if (count($this->entity->getSourceItem()->getStyles()) > 0) {
            $styles    = $this->entity->getSourceItem()->getStyles();
            $legendurl = $styles[count($styles) - 1]->getLegendUrl(); // the last style from object's styles
            if ($legendurl !== null) {
                $configuration["legend"] = array(
                    "url" => $legendurl->getOnlineResource()->getHref(),
                    "width" => intval($legendurl->getWidth()),
                    "height" => intval($legendurl->getHeight()));
            }
        } else if ($this->entity->getSourceInstance()->getSource()->getGetLegendGraphic() !== null &&
            $this->entity->getSourceItem()->getName() !== null &&
            $this->entity->getSourceItem()->getName() !== "") {
            $legend                  = $this->entity->getSourceInstance()->getSource()->getGetLegendGraphic();
            $url                     = $legend->getHttpGet();
            $formats                 = $legend->getFormats();
            $params                  = "service=WMS&request=GetLegendGraphic"
                . "&version=" . $this->entity->getSourceInstance()->getSource()->getVersion()
                . "&layer=" . $this->entity->getSourceItem()->getName()
                . (count($formats) > 0 ? "&format=" . $formats[0] : "")
                . "&sld_version=1.1.0";
            $legendgraphic           = Utils::getHttpUrl($url, $params);
            $configuration["legend"] = array(
                "graphic" => $legendgraphic);
        }
        $configuration["treeOptions"] = array(
            "info" => $this->entity->getInfo(),
            "selected" => $this->entity->getSelected(),
            "toggle" => $this->entity->getSublayer()->count() > 0 ? $this->entity->getToggle() : null,
            "allow" => array(
                "info" => $this->entity->getAllowinfo(),
                "selected" => $this->entity->getAllowselected(),
                "toggle" => $this->entity->getSublayer()->count() > 0 ? $this->entity->getAllowtoggle() : null,
                "reorder" => $this->entity->getAllowreorder(),
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
