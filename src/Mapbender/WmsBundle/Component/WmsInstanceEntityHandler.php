<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 */
class WmsInstanceEntityHandler extends SourceInstanceEntityHandler
{

    /**
     * @inheritdoc
     */
    public function create()
    {
        $this->entity->setTitle($this->entity->getSource()->getTitle());
        $formats = $this->entity->getSource()->getGetMap()->getFormats();
        $this->entity->setFormat(count($formats) > 0 ? $formats[0] : null);
        $infoformats = $this->entity->getSource()->getGetFeatureInfo() !== null ?
            $this->entity->getSource()->getGetFeatureInfo()->getFormats() : array();
        $this->entity->setInfoformat(count($infoformats) > 0 ? $infoformats[0] : null);
        $excformats = $this->entity->getSource()->getExceptionFormats();
        $this->entity->setExceptionformat(count($excformats) > 0 ? $excformats[0] : null);

        $dimensions = array();
        foreach ($this->entity->getSource()->getLayers() as $layer) {
            foreach ($layer->getDimension() as $dimension) {
                $dim = $this->createDimensionInst($dimension);
//                $dim = $dimension;
//                $dim['origExtent'] = $dim['extent'];
                if (!in_array($dim, $dimensions)) {
                    $dimensions[] = $dim;
                }
            }
        }
        $this->entity->setDimensions($dimensions);

        $this->entity->setWeight(-1);

        $this->container->get('doctrine')->getManager()->persist($this->entity);
        $this->container->get('doctrine')->getManager()->flush();

        $wmslayer_root = $this->entity->getSource()->getRootlayer();

        $instLayer = new WmsInstanceLayer();

        $entityHandler = self::createHandler($this->container, $instLayer);
        $entityHandler->create($this->entity, $wmslayer_root);
        $num = 0;
        foreach ($this->entity->getLayerset()->getInstances() as $instance) {
            $instance->setWeight($num);
            $instance->generateConfiguration();
            $this->container->get('doctrine')->getManager()->persist($instance);
            $this->container->get('doctrine')->getManager()->flush();
            $num++;
        }
    }

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $layerHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $layerHandler->remove();
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    public function createDimensionInst(Dimension $dim)
    {
        $diminst = new DimensionInst();
        $diminst->setCurrent($dim->getCurrent());
        $diminst->setDefault($dim->getDefault());
        $diminst->setExtent($dim->getExtent());
        $diminst->setMultipleValues($dim->getMultipleValues());
        $diminst->setName($dim->getName());
        $diminst->setNearestValue($dim->getNearestValue());
        $diminst->setOrigextent($dim->getExtent());
        $diminst->setUnitSymbol($dim->getUnitSymbol());
        $diminst->setUnits($dim->getUnits());
        $diminst->setUse(false);
        $diminst->setType($diminst->findType());
        return $diminst;
    }

}
