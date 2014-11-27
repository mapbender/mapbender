<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\WmsBundle\Component\Dimension;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Entity\WmsLayerSource;

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
            $instHandler = self::createHandler($this->container, $instance);
            $instHandler->getEntity()->setWeight($num);
            $instHandler->generateConfiguration();
            $this->container->get('doctrine')->getManager()->persist($instHandler->getEntity());
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

    /**
     * 
     * @param \Mapbender\WmsBundle\Component\Dimension $dim
     * @return \Mapbender\WmsBundle\Component\DimensionInst
     */
    public function createDimensionInst(Dimension $dim)
    {
        $diminst = new DimensionInst();
        $diminst->setCurrent($dim->getCurrent());
        $diminst->setDefault($dim->getDefault());
        $diminst->setMultipleValues($dim->getMultipleValues());
        $diminst->setName($dim->getName());
        $diminst->setNearestValue($dim->getNearestValue());
        $diminst->setUnitSymbol($dim->getUnitSymbol());
        $diminst->setUnits($dim->getUnits());
        $diminst->setActive(false);
        $diminst->setOrigextent($dim->getExtent());
        $diminst->setExtent($dim->getExtent());
        $diminst->setType($diminst->findType($dim->getExtent()));
        return $diminst;
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
        if ($signer) {
            $configuration['options']['url'] = $signer->signUrl($configuration['options']['url']);
            if ($this->entity->getProxy()) {
                $this->signeUrls($signer, $configuration['children'][0]);
            }
        }
        return $configuration;
    }
    
    
    /**
     * @inheritdoc
     */
    public function generateConfiguration()
    {
        $rootlayer = $this->entity->getRootlayer();
        $llbbox = $rootlayer->getSourceItem()->getLatlonBounds();
        $srses = array(
            $llbbox->getSrs() => array(
                floatval($llbbox->getMinx()),
                floatval($llbbox->getMiny()),
                floatval($llbbox->getMaxx()),
                floatval($llbbox->getMaxy())
            )
        );
        foreach ($rootlayer->getSourceItem()->getBoundingBoxes() as $bbox) {
            $srses = array_merge($srses, array($bbox->getSrs() => array(
                    floatval($bbox->getMinx()),
                    floatval($bbox->getMiny()),
                    floatval($bbox->getMaxx()),
                    floatval($bbox->getMaxy()))));
        }
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($this->entity->getType()));
        $wmsconf->setTitle($this->entity->getTitle());
        $wmsconf->setIsBaseSource($this->entity->isBasesource());

        $options = new WmsInstanceConfigurationOptions();
        $dimensions = array();
        foreach($this->entity->getDimensions() as $dimension){
            if($dimension->getActive()){
                $name = $dimension->getName();
                $dimensions[] = array(
                    'current' => $dimension->getCurrent(),
                    'default' => $dimension->getDefault(),
                    'multipleValues' => $dimension->getMultipleValues(),
                    'name' => $dimension->getName(),
                    '__name' => $name === 'time' || $name === 'elevation' ? $name : "dim_" . $name,
                    'nearestValue' => $dimension->getNearestValue(),
                    'unitSymbol' => $dimension->getUnitSymbol(),
                    'units' => $dimension->getUnits(),
                    'extent' => $dimension->getData($dimension->getExtent()),
                    'type' => $dimension->getType(),
                );
            }
        }
        
        $options->setUrl($this->entity->getSource()->getGetMap()->getHttpGet())
            ->setProxy($this->entity->getProxy())
            ->setVisible($this->entity->getVisible())
            ->setFormat($this->entity->getFormat())
            ->setInfoformat($this->entity->getInfoformat())
            ->setTransparency($this->entity->getTransparency())
            ->setOpacity($this->entity->getOpacity() / 100)
            ->setTiled($this->entity->getTiled())
            ->setBbox($srses)
            ->setDimensions($dimensions);
        $wmsconf->setOptions($options);
        $entityHandler = self::createHandler($this->container, $rootlayer);
        $wmsconf->setChildren(array($entityHandler->generateConfiguration()));
        
        $this->entity->setConfiguration($wmsconf->toArray());
    }
    
    /**
     * Generates a configuration from an yml file
     */
    public function generateYmlConfiguration()
    {
        $this->entity->setSource(new WmsSource());
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($this->entity->getType()));
        $wmsconf->setTitle($this->entity->getTitle());
        $wmsconf->setIsBaseSource($this->entity->isBasesource());

        $options = new WmsInstanceConfigurationOptions();
        $configuration = $this->entity->getConfiguration();
        $options->setUrl($configuration["url"])
            ->setProxy($this->entity->getProxy())
            ->setVisible($this->entity->getVisible())
            ->setFormat($this->entity->getFormat())
            ->setInfoformat($this->entity->getInfoformat())
            ->setTransparency($this->entity->getTransparency())
            ->setOpacity($this->entity->getOpacity() / 100)
            ->setTiled($this->entity->getTiled());

        if (isset($configuration["vendor"])) {
            $options->setVendor($configuration["vendor"]);
        }

        $wmsconf->setOptions($options);

        if (!key_exists("children", $configuration)) {
            $num = 0;
            $rootlayer = new WmsInstanceLayer();
            $rootlayer->setTitle($this->entity->getTitle())
                ->setId($this->entity->getId() . "_" . $num)
                ->setMinScale(!isset($configuration["minScale"]) ? null : $configuration["minScale"])
                ->setMaxScale(!isset($configuration["maxScale"]) ? null : $configuration["maxScale"])
                ->setSelected(!isset($configuration["visible"]) ? false : $configuration["visible"])
                ->setPriority($num)
                ->setSourceItem(new WmsLayerSource())
                ->setSourceInstance($this->entity);
            $rootlayer->setToggle(false);
            $rootlayer->setAllowtoggle(true);
            $this->entity->addLayer($rootlayer);
            foreach ($configuration["layers"] as $layerDef) {
                $num++;
                $layer = new WmsInstanceLayer();
                $layersource = new WmsLayerSource();
                $layersource->setName($layerDef["name"]);
                if (isset($layerDef["legendurl"])) {
                    $style = new Style();
                    $style->setName(null);
                    $style->setTitle(null);
                    $style->setAbstract(null);
                    $legendUrl = new LegendUrl();
                    $legendUrl->setWidth(null);
                    $legendUrl->setHeight(null);
                    $onlineResource = new OnlineResource();
                    $onlineResource->setFormat(null);
                    $onlineResource->setHref($layerDef["legendurl"]);
                    $legendUrl->setOnlineResource($onlineResource);
                    $style->setLegendUrl($legendUrl);
                    $layersource->addStyle($style);
                }
                $layer->setTitle($layerDef["title"])
                    ->setId($this->entity->getId() . '-' . $num)
                    ->setMinScale(!isset($layerDef["minScale"]) ? null : $layerDef["minScale"])
                    ->setMaxScale(!isset($layerDef["maxScale"]) ? null : $layerDef["maxScale"])
                    ->setSelected(!isset($layerDef["visible"]) ? false : $layerDef["visible"])
                    ->setInfo(!isset($layerDef["queryable"]) ? false : $layerDef["queryable"])
                    ->setParent($rootlayer)
                    ->setSourceItem($layersource)
                    ->setSourceInstance($this->entity);
                $layer->setAllowinfo($layer->getInfo() !== null && $layer->getInfo() ? true : false);
                $rootlayer->addSublayer($layer);
                $this->entity->addLayer($layer);
            }
            $instLayHandler = self::createHandler($this->container, $rootlayer);
            $children = array($instLayHandler->generateConfiguration());
            $wmsconf->setChildren($children);
        } else {
            $wmsconf->setChildren($configuration["children"]);
        }
        $this->entity->setConfiguration($wmsconf->toArray());
    }
    
    
    /**
     * Signes urls.
     * 
     * @param Signer $signer signer
     * @param type $layer
     */
    private function signeUrls(Signer $signer, &$layer)
    {
        if (isset($layer['options']['legend'])) {
            if (isset($layer['options']['legend']['graphic'])) {
                $layer['options']['legend']['graphic'] = $signer->signUrl($layer['options']['legend']['graphic']);
            } else if (isset($layer['options']['legend']['url'])) {
                $layer['options']['legend']['url'] = $signer->signUrl($layer['options']['legend']['url']);
            }
        }
        if (isset($layer['children'])) {
            foreach ($layer['children'] as &$child) {
                $this->signeUrls($signer, $child);
            }
        }
    }

}
