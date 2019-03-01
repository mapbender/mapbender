<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmtsBundle\Component\Dimension;
use Mapbender\WmtsBundle\Component\DimensionInst;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;

/**
 * Description of WmtsSourceHandler
 * @property WmtsInstance $entity
 *
 * @author Paul Schmidt
 */
class WmtsInstanceEntityHandler extends SourceInstanceEntityHandler
{
    /**
}
     * @param array $configuration
     * @return WmsInstance
     */
    public function setParameters(array $configuration = array())
    {
        throw new \Exception('not implemented yet');
    }

    /**
     * @inheritdoc
     */
    public function create()
    {
        $this->entity->setTitle($this->entity->getSource()->getTitle());
        $this->entity->setRoottitle($this->entity->getSource()->getTitle());
        $source = $this->entity->getSource();

        $this->entity->setWeight(-1);
        $allowInfo = null;
        foreach ($source->getLayers() as $layer) {
            $instLayer = new WmtsInstanceLayer();
            $instLayerHandler = new WmtsInstanceLayerEntityHandler($this->container, $instLayer);
            $instLayerHandler->create($this->entity, $layer);
            if ($instLayer->getInfoformat()) {
                $allowInfo = true;
            }
        }
        $this->entity->setAllowinfo($allowInfo)
            ->setInfo($allowInfo);

        $num = 0;
        foreach ($this->entity->getLayerset()->getInstances() as $instance) {
            $instance->setWeight($num);
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
        foreach ($this->entity->getLayers() as $layer) {
            self::createHandler($this->container, $layer)->remove();
        }
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
//        $source     = $this->entity->getSource();
//        $this->entity->setFormat(
//            ArrayUtil::getValueFromArray($source->getGetMap()->getFormats(), $this->entity->getFormat(), 0)
//        );
//        $this->entity->setInfoformat(
//            ArrayUtil::getValueFromArray(
//                $source->getGetFeatureInfo() ? $source->getGetFeatureInfo()->getFormats() : array(),
//                $this->entity->getInfoformat(),
//                0
//            )
//        );
//        $this->entity->setExceptionformat(
//            ArrayUtil::getValueFromArray($source->getExceptionFormats(), $this->entity->getExceptionformat(), 0)
//        );
//        $dimensions = $this->updateDimension($this->entity->getDimensions(), $this->getDimensionInst());
//        $this->entity->setDimensions($dimensions);
//
//        # TODO vendorspecific ?
//        self::createHandler($this->container, $this->entity->getRootlayer())
//            ->update($this->entity, $this->entity->getSource()->getRootlayer());
//
//        $this->container->get('doctrine')->getManager()->persist($this->entity);
//        $this->container->get('doctrine')->getManager()->flush();
    }

    /**
     * Creates DimensionInst object
     * @param \Mapbender\WmtsBundle\Component\Dimension $dim
     * @return \Mapbender\WmtsBundle\Component\DimensionInst
     */
    public function createDimensionInst(Dimension $dim)
    {
//        $diminst = new DimensionInst();
//        $diminst->setCurrent($dim->getCurrent());
//        $diminst->setDefault($dim->getDefault());
//        $diminst->setMultipleValues($dim->getMultipleValues());
//        $diminst->setName($dim->getName());
//        $diminst->setNearestValue($dim->getNearestValue());
//        $diminst->setUnitSymbol($dim->getUnitSymbol());
//        $diminst->setUnits($dim->getUnits());
//        $diminst->setActive(false);
//        $diminst->setOrigextent($dim->getExtent());
//        $diminst->setExtent($dim->getExtent());
//        $diminst->setType($diminst->findType($dim->getExtent()));
//        return $diminst;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration(Signer $signer = NULL)
    {
        $wmtsconf = $this->entity->getType() === Source::TYPE_WMTS ?
            new WmtsInstanceConfiguration() : new TmsInstanceConfiguration();

        $wmtsconf->addLayers($this->container, $this->entity);
        return $wmtsconf->toArray();
    }

    public function mergeDimension($dimension, $persist = false)
    {
        $dimensions = $this->entity->getDimensions();
        foreach ($dimensions as $dim) {
            if ($dim->getType() === $dimension->getType()) {
                $dim->setExtent($dimension->getExtent());
                $dim->setDefault($dimension->getDefault());
            }
        }
        $this->entity->setDimensions($dimensions);
        if ($persist) {
            $this->container->get('doctrine')->getManager()->persist($this->entity);
            $this->container->get('doctrine')->getManager()->flush();
        }
    }

    /**
     * @deprecated
     * @internal
     */
    public function getSensitiveVendorSpecific()
    {
        return array();
    }
}
