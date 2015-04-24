<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Component\Dimension;
use Mapbender\WmsBundle\Component\DimensionInst;
use Mapbender\WmsBundle\Component\VendorSpecific;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsSource;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

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
    public function create($persist = true)
    {
        $this->entity->setTitle($this->entity->getSource()->getTitle());
        $source = $this->entity->getSource();
        $this->entity->setFormat(ArrayUtil::getValueFromArray($source->getGetMap()->getFormats(), null, 0));
        $this->entity->setInfoformat(
            ArrayUtil::getValueFromArray(
                $source->getGetFeatureInfo() ? $source->getGetFeatureInfo()->getFormats() : array(),
                null,
                0
            )
        );
        $this->entity->setExceptionformat(ArrayUtil::getValueFromArray($source->getExceptionFormats(), null, 0));

        $dimensions = $this->getDimensionInst();
        $this->entity->setDimensions($dimensions);

        $this->entity->setWeight(-1);
        if ($persist) {
            $this->container->get('doctrine')->getManager()->persist($this->entity);
            $this->container->get('doctrine')->getManager()->flush();
        }
        $wmslayer_root = $this->entity->getSource()->getRootlayer();

        self::createHandler($this->container, new WmsInstanceLayer())->create($this->entity, $wmslayer_root);

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
     * @inheritdoc
     */
    public function update()
    {
        $source     = $this->entity->getSource();
        $this->entity->setFormat(
            ArrayUtil::getValueFromArray($source->getGetMap()->getFormats(), $this->entity->getFormat(), 0)
        );
        $this->entity->setInfoformat(
            ArrayUtil::getValueFromArray(
                $source->getGetFeatureInfo() ? $source->getGetFeatureInfo()->getFormats() : array(),
                $this->entity->getInfoformat(),
                0
            )
        );
        $this->entity->setExceptionformat(
            ArrayUtil::getValueFromArray($source->getExceptionFormats(), $this->entity->getExceptionformat(), 0)
        );
        $dimensions = $this->updateDimension($this->entity->getDimensions(), $this->getDimensionInst());
        $this->entity->setDimensions($dimensions);

        # TODO vendorspecific ?
        self::createHandler($this->container, $this->entity->getRootlayer())
            ->update($this->entity, $this->entity->getSource()->getRootlayer());

        $this->generateConfiguration();
        $this->container->get('doctrine')->getManager()->persist($this->entity);
        $this->container->get('doctrine')->getManager()->flush();
    }

    /**
     * Creates DimensionInst object
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
        $hide = false;
        $params = array();
        foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            if ($handler->isVendorSpecificValueValid()) {
                if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE ||
                    ($vendorspec->getVstype() !== VendorSpecific::TYPE_VS_SIMPLE && !$vendorspec->getHidden())) {
                    $user = $this->container->get('security.context')->getToken()->getUser();
                    $params = array_merge($params, $handler->getKvpConfiguration($user));
                } else {
                    $hide = true;
                }
            }
        }
        if ($hide) {
            $url = $this->container->get('router')->generate(
                'mapbender_core_application_instancetunnel',
                array(
                    'slug' => $this->entity->getLayerset()->getApplication()->getSlug(),
                    'instanceId' => $this->entity->getId()),
                UrlGeneratorInterface::ABSOLUTE_URL
            );
            $configuration['options']['url'] = UrlUtil::validateUrl($url, $params, array());
        } elseif ($signer) {
            $configuration['options']['url'] = UrlUtil::validateUrl($configuration['options']['url'], $params, array());
            $configuration['options']['url'] = $signer->signUrl($configuration['options']['url']);
            if ($this->entity->getProxy()) {
                $this->signeUrls($signer, $configuration['children'][0]);
            }
        }
        $status = $this->entity->getSource()->getStatus();
        $configuration['status'] = $status ? strtolower($status) : strtolower(Source::STATUS_OK);
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function generateConfiguration()
    {
        $rootlayer = $this->entity->getRootlayer();
        $llbbox    = $rootlayer->getSourceItem()->getLatlonBounds();
        $srses     = array(
            $llbbox->getSrs() => array(
                floatval($llbbox->getMinx()),
                floatval($llbbox->getMiny()),
                floatval($llbbox->getMaxx()),
                floatval($llbbox->getMaxy())
            )
        );
        foreach ($rootlayer->getSourceItem()->getBoundingBoxes() as $bbox) {
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
        $wmsconf = new WmsInstanceConfiguration();
        $wmsconf->setType(strtolower($this->entity->getType()));
        $wmsconf->setTitle($this->entity->getTitle());
        $wmsconf->setIsBaseSource($this->entity->isBasesource());

        $options    = new WmsInstanceConfigurationOptions();
        $dimensions = array();
        foreach ($this->entity->getDimensions() as $dimension) {
            if ($dimension->getActive()) {
                $dimensions[] = $dimension->getConfiguration();
                if ($dimension->getDefault()) {
                    $help = array($dimension->getParameterName() => $dimension->getDefault());
                    $options->setUrl(UrlUtil::validateUrl($options->getUrl(), $help, array()));
                }
            }
        }
        $vendorsecifics = array();
        foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
            $handler = new VendorSpecificHandler($vendorspec);
            /* add to url only simple vendor specific with valid default value */
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE && $handler->isVendorSpecificValueValid()) {
                $vendorsecifics[] = $handler->getConfiguration();
                $help = $handler->getKvpConfiguration(null);
                $options->setUrl(UrlUtil::validateUrl($options->getUrl(), $help, array()));
            }
        }
        $options$options->setUrl($this->entity->getSource()->getGetMap()->getHttpGet())
            ->setProxy($this->entity->getProxy())
            ->setVisible($this->entity->getVisible())
            ->setFormat($this->entity->getFormat())
            ->setInfoformat($this->entity->getInfoformat())
            ->setTransparency($this->entity->getTransparency())
            ->setOpacity($this->entity->getOpacity() / 100)
            ->setTiled($this->entity->getTiled())
            ->setBbox($srses)
            ->setDimensions($dimensions)
            ->setVendorspecifics($vendorsecifics);
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

        $options       = new WmsInstanceConfigurationOptions();
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
            $num       = 0;
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
                $layer       = new WmsInstanceLayer();
                $layersource = new WmsLayerSource();
                $layersource->setName($layerDef["name"]);
                if (isset($layerDef["legendurl"])) {
                    $style          = new Style();
                    $style->setName(null);
                    $style->setTitle(null);
                    $style->setAbstract(null);
                    $legendUrl      = new LegendUrl();
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
            $children       = array($instLayHandler->generateConfiguration());
            $wmsconf->setChildren($children);
        } else {
            $wmsconf->setChildren($configuration["children"]);
        }
        $this->entity->setConfiguration($wmsconf->toArray());
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

    /**
     * @inheritdoc
     */
    public function getSensitiveVendorSpecific()
    {
        $vsarr = array();
        $user = $this->container->get('security.context')->getToken()->getUser();
        if ($user instanceof AdvancedUserInterface) {
            foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
                $handler = new VendorSpecificHandler($vendorspec);
                if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_USER) {
                    $value = $handler->getVendorSpecificValue($user);
                    if ($value) {
                        $vsarr[$vendorspec->getParameterName()] = $value;
                    }
                } elseif ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_GROUP) {
                    $groups = array();
                    foreach ($user->getGroups() as $group) {
                        $value = $handler->getVendorSpecificValue($group);
                        if ($value) {
                            $vsarr[$vendorspec->getParameterName()] = $value;
                        }
                    }
                    if (count($groups)) {
                        $vsarr[$vendorspec->getParameterName()] = implode(',', $groups);
                    }
                }
            }
        }
        if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE) {
            $value = $handler->getVendorSpecificValue(null);
            if ($value) {
                $vsarr[$vendorspec->getParameterName()] = $value;
            }
        }
        return $vsarr;
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
}
