<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Utils\ClassPropertiesParser;
use Mapbender\CoreBundle\Utils\EntityAnnotationParser;
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
        if ($persist) {
            $this->container->get('doctrine')->getManager()->persist($this->entity);
            $this->container->get('doctrine')->getManager()->flush();
        }
        $wmslayer_root = $this->entity->getSource()->getRootlayer();

        $instLayer = new WmsInstanceLayer();

        $entityHandler = self::createHandler($this->container, $instLayer);
        $entityHandler->create($this->entity, $wmslayer_root, 0, $persist);
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
        $vctunnel = false;
        foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
            if ($vendorspec->getVstype() !== VendorSpecific::TYPE_VS_SIMPLE) {
                $vctunnel = true;
                break;
            }
        }
        if ($this->entity->getSource()->getUsername() || $vctunnel) {
            $url = $this->container->get('router')->generate(
                'mapbender_core_application_instancetunnel',
                array(
                'slug' => $this->entity->getLayerset()->getApplication()->getSlug(),
                'instanceId' => $this->entity->getId()
                ), UrlGeneratorInterface::ABSOLUTE_URL);
            $configuration['options']['url'] = $url;
        } elseif ($signer) {
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
            $srses = array_merge($srses,
                                 array($bbox->getSrs() => array(
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
        $options->setUrl($this->entity->getSource()->getGetMap()->getHttpGet());
        $dimensions = array();
        foreach ($this->entity->getDimensions() as $dimension) {
            if ($dimension->getActive()) {
                $dimensions[] = $dimension->getConfiguration();
                if ($dimension->getDefault()) {
                    $options->setUrl(
                        UrlUtil::validateUrl($options->getUrl(), array(),
                                             array($dimension->getParameterName() => $dimension->getDefault())));
                }
            }
        }
        $vendorsecifics = array();
        foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE || !$vendorspec->getUsetunnel()) {
                $vendorsecifics[] = $vendorspec->getConfiguration();
                if ($vendorspec->getDefault()) {
                    $options->setUrl(UrlUtil::validateUrl(
                            $options->getUrl(), array(),
                            array($vendorspec->getParameterName() => $vendorspec->getDefault())));
                }
            }
        }
        $options->setProxy($this->entity->getProxy())
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

    /**
     * @inheritdoc
     */
    public function getSensitiveVendorSpecific()
    {
        $vsarr = array();
        $match = '/\$.+\$/';
        $securityContext = $this->container->get('security.context');
        $user = $securityContext->getToken()->getUser();
        if ($user instanceof AdvancedUserInterface) {
            foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
                if ($vendorspec->getVstype() && $vendorspec->getVstype() === VendorSpecific::TYPE_VS_USERNAME) {
                    $value = $this->getVendorSpecificValue($vendorspec, $user);
                    if ($value) {
                        $vsarr[$vendorspec->getParameterName()] = $value;
                    }
                } elseif ($vendorspec->getVstype() && $vendorspec->getVstype() === VendorSpecific::TYPE_VS_GROUPNAME) {
                    $groups = array();
                    foreach ($user->getGroups() as $group) {
                        $value = $this->getVendorSpecificValue($vendorspec, $group);
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
        return $vsarr;
    }

    private function getVendorSpecificValue(VendorSpecific $vendorspec, $object)
    {
        $value = $vendorspec->getDefault() ? $vendorspec->getDefault() : $vendorspec->getExtent();
        $length = strlen($value);
        if ($length > 2 && strpos($value, '$', 0) === 0 && strpos($value, '$', $length - 2) === $length - 1) {
            $value = str_replace('$', '', $value);
            $fields = ClassPropertiesParser::parseFields(get_class($object));
            if (isset($fields[$value]) && isset($fields[$value][EntityAnnotationParser::GETTER])) {
                $reflectionMethod = new \ReflectionMethod(get_class($object), $fields[$value][EntityAnnotationParser::GETTER]);
                $fieldValue = $reflectionMethod->invoke($object);
                return $reflectionMethod->invoke($object);
            }
        } else {
            return $value;
        }
        return null;
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

}
