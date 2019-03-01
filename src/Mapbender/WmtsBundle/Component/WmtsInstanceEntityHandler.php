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
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmtsBundle\Component\Dimension;
use Mapbender\WmtsBundle\Component\DimensionInst;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * @param array $configuration
     * @return WmtsInstance
     */
    public function configure(array $configuration = array())
    {
        return $this->entity;
    }

    /**
     * @inheritdoc
     */
    public function create($persist = true)
    {
        $this->entity->setTitle($this->entity->getSource()->getTitle());
        $this->entity->setRoottitle($this->entity->getSource()->getTitle());
        $source = $this->entity->getSource();
        // TODO create dimansions

        $this->entity->setWeight(-1);
        if ($persist) {
            $this->container->get('doctrine')->getManager()->persist($this->entity);
            $this->container->get('doctrine')->getManager()->flush();
        }
        $allowInfo = null;
        foreach ($source->getLayers() as $layer) {
            $instLayer = new WmtsInstanceLayer();
            self::createHandler($this->container, $instLayer)->create($this->entity, $layer);
            if ($instLayer->getInfoformat()) {
                $allowInfo = true;
            }
        }
        $this->entity->setAllowinfo($allowInfo)
            ->setInfo($allowInfo);

        $num = 0;
        foreach ($this->entity->getLayerset()->getInstances() as $instance) {
            $instance->setWeight($num);
            $instHandler = self::createHandler($this->container, $instance);
            $instHandler->generateConfiguration();
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
//        $this->generateConfiguration();
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
        if ($this->entity->getSource() === null) { // from yaml
            $this->generateYmlConfiguration();
        } else {
            if ($this->entity->getConfiguration() === null) {
                $this->generateConfiguration();
            }
        }
        $configuration = $this->entity->getConfiguration();
        if ($this->entity->getSource()->getUsername()) {
            $url                             = $this->container->get('router')->generate(
                'mapbender_core_application_instancebasicauth',
                array(
                    'slug' => $this->entity->getLayerset()->getApplication()->getSlug(),
                    'instanceId' => $this->entity->getId()
                ),
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $configuration['options']['url'] = $url;
        } // not yet supported - RESTful
//        elseif ($signer) {
//            foreach ($configuration['layers'] as &$layer) {
//                $layer['options']['url'] = $signer->signUrl($layer['options']['url']);
//            }
//        }
        $status = $this->entity->getSource()->getStatus();
        $configuration['status'] = $status ? strtolower($status) : strtolower(Source::STATUS_OK);
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function generateConfiguration()
    {
        $wmtsconf = $this->entity->getType() === Source::TYPE_WMTS ?
            new WmtsInstanceConfiguration() : new TmsInstanceConfiguration();
        $wmtsconf->setType(strtolower($this->entity->getType()));
        $wmtsconf->setTitle($this->entity->getTitle());
        $wmtsconf->setIsBaseSource($this->entity->isBasesource());

        $options    = $this->entity->getType() === Source::TYPE_WMTS ?
            new WmtsInstanceConfigurationOptions() : new TmsInstanceConfigurationOptions();
//        $dimensions = array();
//        foreach ($this->entity->getDimensions() as $dimension) {
//            if ($dimension->getActive()) {
//                $name         = $dimension->getName();
//                $dimensions[] = array(
//                    'current' => $dimension->getCurrent(),
//                    'default' => $dimension->getDefault(),
//                    'multipleValues' => $dimension->getMultipleValues(),
//                    'name' => $dimension->getName(),
//                    '__name' => $name === 'time' || $name === 'elevation' ? $name : "dim_" . $name,
//                    'nearestValue' => $dimension->getNearestValue(),
//                    'unitSymbol' => $dimension->getUnitSymbol(),
//                    'units' => $dimension->getUnits(),
//                    'extent' => $dimension->getData($dimension->getExtent()),
//                    'type' => $dimension->getType(),
//                );
//            }
//        }
        $options
            ->setProxy($this->entity->getProxy())
            ->setVisible($this->entity->getVisible())
            ->setOpacity($this->entity->getOpacity() / 100)
//            ->setDimensions($dimensions)
            ;
        $wmtsconf->setOptions($options);
        $rootnode = $this->createRootNode();
        $wmtsconf->addLayers($this->container, $this->entity, $rootnode);
        $this->entity->setConfiguration($wmtsconf->toArray());
    }

    /**
     * Generates a configuration from an yml file
     */
    public function generateYmlConfiguration()
    {
//        $this->entity->setSource(new WmtsSource());
//        $wmtsconf = new WmtsInstanceConfiguration();
//        $wmtsconf->setType(strtolower($this->entity->getType()));
//        $wmtsconf->setTitle($this->entity->getTitle());
//        $wmtsconf->setIsBaseSource($this->entity->isBasesource());
//
//        $options       = new WmtsInstanceConfigurationOptions();
//        $configuration = $this->entity->getConfiguration();
//        $options->setUrl($configuration["url"])
//            ->setProxy($this->entity->getProxy())
//            ->setVisible($this->entity->getVisible())
//            ->setFormat($this->entity->getFormat())
//            ->setInfoformat($this->entity->getInfoformat())
//            ->setTransparency($this->entity->getTransparency())
//            ->setOpacity($this->entity->getOpacity() / 100)
//            ->setTiled($this->entity->getTiled());
//
//        if (isset($configuration["vendor"])) {
//            $options->setVendor($configuration["vendor"]);
//        }
//
//        $wmtsconf->setOptions($options);
//
//        if (!key_exists("children", $configuration)) {
//            $num       = 0;
//            $rootlayer = new WmtsInstanceLayer();
//            $rootlayer->setTitle($this->entity->getTitle())
//                ->setId($this->entity->getId() . "_" . $num)
//                ->setMinScale(!isset($configuration["minScale"]) ? null : $configuration["minScale"])
//                ->setMaxScale(!isset($configuration["maxScale"]) ? null : $configuration["maxScale"])
//                ->setSelected(!isset($configuration["visible"]) ? false : $configuration["visible"])
////                ->setPriority($num)
//                ->setSourceItem(new WmtsLayerSource())
//                ->setSourceInstance($this->entity);
//            $rootlayer->setToggle(false);
//            $rootlayer->setAllowtoggle(true);
//            $this->entity->addLayer($rootlayer);
//            foreach ($configuration["layers"] as $layerDef) {
//                $num++;
//                $layer       = new WmtsInstanceLayer();
//                $layersource = new WmtsLayerSource();
//                $layersource->setName($layerDef["name"]);
//                if (isset($layerDef["legendurl"])) {
//                    $style          = new Style();
//                    $style->setName(null);
//                    $style->setTitle(null);
//                    $style->setAbstract(null);
//                    $legendUrl      = new LegendUrl();
//                    $legendUrl->setWidth(null);
//                    $legendUrl->setHeight(null);
//                    $onlineResource = new OnlineResource();
//                    $onlineResource->setFormat(null);
//                    $onlineResource->setHref($layerDef["legendurl"]);
//                    $legendUrl->setOnlineResource($onlineResource);
//                    $style->setLegendUrl($legendUrl);
//                    $layersource->addStyle($style);
//                }
//                $layer->setTitle($layerDef["title"])
//                    ->setId($this->entity->getId() . '-' . $num)
//                    ->setMinScale(!isset($layerDef["minScale"]) ? null : $layerDef["minScale"])
//                    ->setMaxScale(!isset($layerDef["maxScale"]) ? null : $layerDef["maxScale"])
//                    ->setSelected(!isset($layerDef["visible"]) ? false : $layerDef["visible"])
//                    ->setInfo(!isset($layerDef["queryable"]) ? false : $layerDef["queryable"])
//                    ->setParent($rootlayer)
//                    ->setSourceItem($layersource)
//                    ->setSourceInstance($this->entity);
//                $layer->setAllowinfo($layer->getInfo() !== null && $layer->getInfo() ? true : false);
//                $rootlayer->addSublayer($layer);
//                $this->entity->addLayer($layer);
//            }
//            $instLayHandler = self::createHandler($this->container, $rootlayer);
//            $children       = array($instLayHandler->generateConfiguration());
//            $wmtsconf->setChildren($children);
//        } else {
//            $wmtsconf->setChildren($configuration["children"]);
//        }
//        $this->entity->setConfiguration($wmtsconf->toArray());
    }

    /**
     * Signes urls.
     * @param Signer $signer signer
     * @param type $layer
     */
    private function signeUrls(Signer $signer, &$layer)
    {
        if (isset($layer['options']['legend'])) {
            if (isset($layer['options']['legend']['graphic'])) {
                $layer['options']['legend']['graphic'] = $signer->signUrl($layer['options']['legend']['graphic']);
            } elseif (isset($layer['options']['legend']['url'])) {
                $layer['options']['legend']['url'] = $signer->signUrl($layer['options']['legend']['url']);
            }
        }
        if (isset($layer['children'])) {
            foreach ($layer['children'] as &$child) {
                $this->signeUrls($signer, $child);
            }
        }
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

    private function getDimensionInst()
    {
        $dimensions = array();
        foreach ($this->entity->getSource()->getLayers() as $layer) {
            foreach ($layer->getDimension() as $dimension) {
                $dim = $this->createDimensionInst($dimension);
                if (!in_array($dim, $dimensions)) {
                    $dimensions[] = $dim;
                }
            }
        }
        return $dimensions;
    }

    private function findDimension(DimensionInst $dimension, $dimensionList)
    {
        foreach ($dimensionList as $help) {
            /* check if dimensions equals (check only origextent) */
            if ($help->getOrigextent() === $dimension->getOrigextent() &&
                $help->getName() === $dimension->getName() &&
                $help->getUnits() === $dimension->getUnits()) {
                return $help;
            }
        }
        return null;
    }

    private function updateDimension(array $dimensionsOld, array $dimensionsNew)
    {
        $dimensions = array();
        foreach ($dimensionsNew as $dimNew) {
            $dimension    = $this->findDimension($dimNew, $dimensionsOld);
            $dimension    = $dimension ? clone $dimension : clone $dimNew;
            /* replace attribute values */
            $dimension->setUnitSymbol($dimNew->getUnitSymbol());
            $dimension->setNearestValue($dimNew->getNearestValue());
            $dimension->setCurrent($dimNew->getCurrent());
            $dimension->setMultipleValues($dimNew->getMultipleValues());
            $dimensions[] = $dimension;
        }
        return $dimensions;
    }

    private function createRootNode()
    {
        $root = new WmtsLayerSource();
        $rootInst = new WmtsInstanceLayer();
        $rootInst->setTitle($this->entity->getRoottitle());
        $rootInst->setSourceItem($root);
        $rootInst->setSourceInstance($this->entity);
        $rootInst->setActive($this->entity->getActive())
            ->setAllowinfo($this->entity->getAllowinfo())
            ->setInfo($this->entity->getInfo())
            ->setAllowtoggle($this->entity->getAllowtoggle())
            ->setToggle($this->entity->getToggle());
        $rootlayerHandler = self::createHandler($this->container, $rootInst);
        return $rootlayerHandler->generateConfiguration();
    }

    /**
     * @inheritdoc
     */
    public function getSensitiveVendorSpecific()
    {
        return array();
    }
}
