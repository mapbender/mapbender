<?php
namespace Mapbender\WmsBundle\Component;

use Doctrine\ORM\EntityManager;
use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\Component\Presenter\WmsSourceService;
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
     * Populates bound instance with array values. Used exclusively by
     * ApplicationYAMLMapper ..?
     *
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
     * Copies attributes from bound instance's source to the bound instance.
     * I.e. does not work for a new instance until you have called ->setSource on the WmsInstance yourself,
     * and does not achieve anything useful for an already configured instance loaded from the DB (though it's
     * expensive!).
     * If your source changed, and you want to push updates to your instance, you want to call update, not create.
     *
     * @deprecated for misleading wording, arcane usage, redundant container dependency
     */
    public function create()
    {
        $this->entity->populateFromSource($this->entity->getSource());
        $this->getService()->initializeInstance($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function save()
    {
        if ($this->entity->getRootlayer()) {
            $rootlayerSaveHandler = new WmsInstanceLayerEntityHandler($this->container, $this->entity->getRootlayer());
            $rootlayerSaveHandler->save();
        }
        $layerSet = $this->entity->getLayerset();
        $num = 0;
        foreach ($layerSet->getInstances() as $instance) {
            /** @var WmsInstance $instance */
            $instance->setWeight($num);
            $this->container->get('doctrine')->getManager()->persist($instance);
            $num++;
        }
        $application = $layerSet->getApplication();
        $application->setUpdated(new \DateTime('now'));
        /** @var EntityManager $entityManager */
        $entityManager = $this->container->get('doctrine')->getManager();
        $entityManager->persist($application);
        $entityManager->persist($this->entity);
    }
    

    /**
     * @inheritdoc
     */
    public function remove()
    {
        /**
         * @todo: layerHandler->remve is redundant now, but it may require an automatic
         *     doctrine:schema:update --force
         *     before it can be removed
         */
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
        $layerDimensionInsts = $source->dimensionInstancesFactory();
        $dimensions = $this->updateDimension($this->entity->getDimensions(), $layerDimensionInsts);
        $this->entity->setDimensions($dimensions);

        # TODO vendorspecific for layer specific parameters
        /** @var WmsInstanceLayerEntityHandler $rootUpdateHandler */
        $rootUpdateHandler = new WmsInstanceLayerEntityHandler($this->container, $this->entity->getRootlayer());
        $rootUpdateHandler->update($this->entity, $this->entity->getSource()->getRootlayer());

        $this->container->get('doctrine')->getManager()->persist(
            $this->entity->getLayerset()->getApplication()->setUpdated(new \DateTime('now')));
        $this->container->get('doctrine')->getManager()->persist($this->entity);
    }

    /**
     * Creates DimensionInst object, copies attributes from given Dimension object
     * @param \Mapbender\WmsBundle\Component\Dimension $dim
     * @return \Mapbender\WmsBundle\Component\DimensionInst
     * @deprecated for redundant container dependency, call DimensionInst::fromDimension directly
     */
    public function createDimensionInst(Dimension $dim)
    {
        return DimensionInst::fromDimension($dim);
    }

    /**
     * @inheritdoc
     * @deprecated, use the appropriate service directly
     */
    public function getConfiguration(Signer $signer = null)
    {
        $service = $this->getService();
        return $service->getConfiguration($this->entity);
    }

    /**
     * Does nothing, returns nothing
     * @deprecated
     */
    public function generateConfiguration()
    {
    }

    /**
     * @return array
     * @deprecated, use the service directly
     */
    protected function getRootLayerConfig()
    {
        /** @var WmsSourceService $service */
        $service = $this->getService();
        return $service->getRootLayerConfig($this->entity);
    }

    /**
     * @inheritdoc
     */
    public function getSensitiveVendorSpecific()
    {
        $vsarr = array();
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
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
}
