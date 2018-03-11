<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\BoundingBox;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\WmsBundle\Element\DimensionsHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;

/**
 * Description of WmsSourceHandler
 *
 * @author Paul Schmidt
 *
 * @property WmsInstance $entity
 */
class WmsInstanceEntityHandler extends SourceInstanceEntityHandler
{
    /**
     * @param array $configuration
     * @return WmsInstance
     */
    public function setParameters(array $configuration = array())
    {
        /** @var WmsInstance $sourceInstance */
        if (!$this->entity->getSource()) {
            $this->entity->setSource(new WmsSource());
        }
        $source = $this->entity->getSource();
        $source->setId(ArrayUtil::hasSet($configuration, 'id', ""))
            ->setTitle(ArrayUtil::hasSet($configuration, 'id', ""));
        $source->setVersion(ArrayUtil::hasSet($configuration, 'version', "1.1.1"));
        $source->setOriginUrl(ArrayUtil::hasSet($configuration, 'url'));
        $source->setGetMap(new RequestInformation());
        $source->getGetMap()->addFormat(ArrayUtil::hasSet($configuration, 'format', true))
            ->setHttpGet(ArrayUtil::hasSet($configuration, 'url'));
        if (isset($configuration['info_format'])) {
            $source->setGetFeatureInfo(new RequestInformation());
            $source->getGetFeatureInfo()->addFormat(ArrayUtil::hasSet($configuration, 'info_format', true))
                ->setHttpGet(ArrayUtil::hasSet($configuration, 'url'));
        }

        $this->entity
            ->setId(ArrayUtil::hasSet($configuration, 'id', null))
            ->setTitle(ArrayUtil::hasSet($configuration, 'title', ""))
            ->setWeight(ArrayUtil::hasSet($configuration, 'weight', 0))
            ->setLayerset(ArrayUtil::hasSet($configuration, 'layerset'))
            ->setProxy(ArrayUtil::hasSet($configuration, 'proxy', false))
            ->setVisible(ArrayUtil::hasSet($configuration, 'visible', true))
            ->setFormat(ArrayUtil::hasSet($configuration, 'format', true))
            ->setInfoformat(ArrayUtil::hasSet($configuration, 'info_format'))
            ->setTransparency(ArrayUtil::hasSet($configuration, 'transparent', true))
            ->setOpacity(ArrayUtil::hasSet($configuration, 'opacity', 100))
            ->setTiled(ArrayUtil::hasSet($configuration, 'tiled', false))
            ->setBaseSource(ArrayUtil::hasSet($configuration, 'isBaseSource', true));

        $rootMinScale = !isset($configuration["minScale"]) ? null : $configuration["minScale"];
        $rootMaxScale =!isset($configuration["maxScale"]) ? null : $configuration["maxScale"];
        $rootScaleObj = new MinMax($rootMinScale, $rootMaxScale);

        $num  = 0;
        $layersourceroot = new WmsLayerSource();
        $layersourceroot->setPriority($num)
            ->setSource($source)
            ->setTitle($this->entity->getTitle())
            ->setScale($rootScaleObj)
            ->setId($source->getId() . '_' . $num);
        $source->addLayer($layersourceroot);
        $rootInstLayer = new WmsInstanceLayer();
        $rootInstLayer->setTitle($this->entity->getTitle())
            ->setId($this->entity->getId() . "_" . $num)
            ->setSelected(!isset($configuration["visible"]) ? false : $configuration["visible"])
            ->setPriority($num)
            ->setSourceItem($layersourceroot)
            ->setSourceInstance($this->entity)
            ->setToggle(false)
            ->setAllowtoggle(true);
        $this->entity->addLayer($rootInstLayer);
        foreach ($configuration["layers"] as $layerDef) {
            $num++;
            $layersource = new WmsLayerSource();
            $layersource->setSource($source)
                ->setName($layerDef["name"])
                ->setTitle($layerDef['title'])
                ->setParent($layersourceroot)
                ->setId($this->entity->getId() . '_' . $num);
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
            $layersourceroot->addSublayer($layersource);
            $source->addLayer($layersource);
            $layerInst       = new WmsInstanceLayer();
            $layerInst->setTitle($layerDef["title"])
                ->setId($this->entity->getId() . '_' . $num)
                ->setMinScale(!isset($layerDef["minScale"]) ? null : $layerDef["minScale"])
                ->setMaxScale(!isset($layerDef["maxScale"]) ? null : $layerDef["maxScale"])
                ->setSelected(!isset($layerDef["visible"]) ? false : $layerDef["visible"])
                ->setInfo(!isset($layerDef["queryable"]) ? false : $layerDef["queryable"])
                ->setParent($rootInstLayer)
                ->setSourceItem($layersource)
                ->setSourceInstance($this->entity)
                ->setAllowinfo($layerInst->getInfo() !== null && $layerInst->getInfo() ? true : false);
            $rootInstLayer->addSublayer($layerInst);
            $this->entity->addLayer($layerInst);
        }
        return $this->entity;
    }

    /**
     * Copies attributes from bound instance's source to the bound instance
     * @deprecated
     * @inheritdoc
     */
    public function create()
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
        $wmslayer_root = $this->entity->getSource()->getRootlayer();

        $newInstanceLayerHandler = new WmsInstanceLayerEntityHandler($this->container, new WmsInstanceLayer());
        $newInstanceLayerHandler->create($this->entity, $wmslayer_root);
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if ($this->entity->getRootlayer()) {
            self::createHandler($this->container, $this->entity->getRootlayer())->save();
        }
        $num = 0;
        foreach ($this->entity->getLayerset()->getInstances() as $instance) {
            /** @var WmsInstanceEntityHandler $instHandler */
            $instHandler = self::createHandler($this->container, $instance);
            $instHandler->getEntity()->setWeight($num);
            $instHandler->generateConfiguration();
            $this->container->get('doctrine')->getManager()->persist($instHandler->getEntity());
            $num++;
        }
        $this->container->get('doctrine')->getManager()->persist(
            $this->entity->getLayerset()->getApplication()->setUpdated(new \DateTime('now')));
        $this->container->get('doctrine')->getManager()->persist($this->entity);
    }
    

    /**
     * @inheritdoc
     */
    public function remove()
    {
        $layerHandler = self::createHandler($this->container, $this->entity->getRootlayer());
        $layerHandler->remove();
        $this->container->get('doctrine')->getManager()->persist(
            $this->entity->getLayerset()->getApplication()->setUpdated(new \DateTime('now')));
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

        # TODO vendorspecific for layer specific parameters
        self::createHandler($this->container, $this->entity->getRootlayer())
            ->update($this->entity, $this->entity->getSource()->getRootlayer());

        $this->generateConfiguration();
        $this->container->get('doctrine')->getManager()->persist(
            $this->entity->getLayerset()->getApplication()->setUpdated(new \DateTime('now')));
        $this->container->get('doctrine')->getManager()->persist($this->entity);
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
    public function getConfiguration(Signer $signer = null)
    {
        if ($this->entity->getConfiguration() === null) {
            $this->generateConfiguration();
        }
        $configuration = $this->entity->getConfiguration();
        $layerConfig = $this->getRootLayerConfig();
        if ($layerConfig) {
            $configuration['children'] = array($layerConfig);
        }
        if (!$this->isConfigurationValid($configuration)) {
            return null;
        }
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
        if ($hide || $this->entity->getSource()->getUsername()) {
            $url = $this->getTunnel()->getPublicBaseUrl();
            $configuration['options']['url'] = UrlUtil::validateUrl($url, $params, array());
            // remove ows proxy for a tunnel connection
            $configuration['options']['tunnel'] = true;
        } elseif ($signer) {
            $configuration['options']['url'] = UrlUtil::validateUrl($configuration['options']['url'], $params, array());
            $configuration['options']['url'] = $signer->signUrl($configuration['options']['url']);
            if ($this->entity->getProxy()) {
                $this->signeUrls($signer, $configuration['children'][0]);
            }
        }
        $status = $this->entity->getSource()->getStatus();
        $configuration['status'] = $status && $status === Source::STATUS_UNREACHABLE ? 'error' : 'ok';
        return $configuration;
    }

    /**
     * @param WmsInstanceLayer $rootLayer
     * @return BoundingBox[]
     */
    private function extractBoundingBoxes(WmsInstanceLayer $rootLayer)
    {
        $sourceItem = $rootLayer->getSourceItem();
        $bboxes = array();
        $latLonBounds = $sourceItem->getLatlonBounds();
        if ($latLonBounds) {
            $bboxes[] = $latLonBounds;
        }
        return array_merge($bboxes, $sourceItem->getBoundingBoxes());
    }

    /**
     * Modifies the bound entity, populates `configuration` attribute, returns nothing
     */
    public function generateConfiguration()
    {
        $wmsconf = WmsInstanceConfiguration::fromEntity($this->entity);

        $persistableConfig = $wmsconf->toArray();

        $this->entity->setConfiguration($persistableConfig);
    }

    protected function getRootLayerConfig()
    {
        $rootlayer = $this->entity->getRootlayer();
        $entityHandler = new WmsInstanceLayerEntityHandler($this->container, $rootlayer);
        $rootLayerConfig = $entityHandler->generateConfiguration();
        return $rootLayerConfig;
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
        foreach ($this->entity->getVendorspecifics() as $key => $vendorspec) {
            if ($vendorspec->getVstype() === VendorSpecific::TYPE_VS_SIMPLE) {
                $value = $handler->getVendorSpecificValue(null);
                if ($value) {
                    $vsarr[$vendorspec->getParameterName()] = $value;
                }
            }
        }
        return $vsarr;
    }

    /**
     * Copies Extent and Default from passed DimensionInst to any DimensionInst stored
     * in bound WmsInstance that match the same Type.
     *
     * @param DimensionInst $dimension
     * @deprecated we do not modify entities for presentation or frontend purposes
     *    This was only used by DimensionsHandler::postSave, which is now removed.
     *    The implementation has been moved directly into DimensionsHandler.
     */
    public function mergeDimension($dimension)
    {
        DimensionsHandler::reconfigureDimensions($this->entity, $dimension);
    }

    /**
     * @return array
     */
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

    /**
     * @param \Mapbender\WmsBundle\Component\DimensionInst $dimension
     * @param  DimensionInst[]                             $dimensionList
     * @return null
     */
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

    /**
     * @param array $dimensionsOld
     * @param array $dimensionsNew
     * @return array
     */
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

    /**
     * Checks if a configuraiton is valid.
     * @param array $configuration configuration of an instance or a layer
     * @param boolean $isLayer if it is a layer's configurationis it a layer's configuration?
     * @return boolean true if a configuration is valid otherwise false
     */
    private function isConfigurationValid(array $configuration, $isLayer = false)
    {
        if (!$isLayer) {
            // TODO another tests for instance configuration
            /* check if root exists and has children */
            if (count($configuration['children']) !== 1 || !isset($configuration['children'][0]['children'])) {
                return false;
            } else {
                foreach ($configuration['children'][0]['children'] as $childConfig) {
                    if ($this->isConfigurationValid($childConfig, true)) {
                        return true;
                    }
                }
            }
        } else {
            if (isset($configuration['children'])) { // > 2 simple layers -> OK.
                foreach ($configuration['children'] as $childConfig) {
                    if ($this->isConfigurationValid($childConfig, true)) {
                        return true;
                    }
                }
            } else {
                return true;
            }
        }
        return false;
    }
}
