<?php
namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\Signer;
use Mapbender\CoreBundle\Component\SourceInstanceEntityHandler;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\WmsBundle\Component\Presenter\WmsSourceService;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

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
        if (!$this->entity->getSource()) {
            $this->entity->setSource(new WmsSource());
        }
        $source = $this->entity->getSource();
        $source->setId(!isset($configuration['id']) ? '' : $configuration['id'])
            ->setTitle(!isset($configuration['id']) ? '' : $configuration['id'])
            ->setVersion(!isset($configuration['version']) ? '1.1.1' : $configuration['version'])
            ->setOriginUrl(!isset($configuration['url']) ? null : $configuration['url'])
            ->setGetMap(new RequestInformation())
            ->getGetMap()->addFormat(!isset($configuration['format']) ? 'image/png' : $configuration['format'])
            ->setHttpGet(!isset($configuration['url']) ? null : $configuration['url']);
        if (isset($configuration['info_format'])) {
            $source->setGetFeatureInfo(new RequestInformation())
                ->getGetFeatureInfo()->addFormat(!isset($configuration['info_format']) ? 'text/html' : $configuration['info_format'])
                ->setHttpGet(!isset($configuration['url']) ? null : $configuration['url']);
        }

        $this->entity
            ->setId(!isset($configuration['id']) ? null : $configuration['id'])
            ->setTitle(!isset($configuration['title']) ? '' : $configuration['title'])
            ->setWeight(!isset($configuration['weight']) ? -1 : $configuration['weight'])
            ->setLayerset(!isset($configuration['layerset']) ? null : $configuration['layerset'])
            ->setProxy(!isset($configuration['proxy']) ? false : $configuration['proxy'])
            ->setVisible(!isset($configuration['visible']) ? true : $configuration['visible'])
            ->setFormat(!isset($configuration['format']) ? 'image/png' : $configuration['format'])
            ->setInfoformat(!isset($configuration['info_format']) ? 'text/html' : $configuration['info_format'])
            ->setTransparency(!isset($configuration['transparent']) ? true : $configuration['transparent'])
            ->setOpacity(!isset($configuration['opacity']) ? 100 : $configuration['opacity'])
            ->setTiled(!isset($configuration['tiled']) ? false : $configuration['tiled'])
            ->setBasesource(!isset($configuration['isBaseSource']) ? true : $configuration['isBaseSource'])
            ->setLayerOrder(!isset($configuration['layerorder']) ? 'standard' : $configuration['layerorder']);

        $rootMinScale = !isset($configuration["minScale"]) ? null : $configuration["minScale"];
        $rootMaxScale = !isset($configuration["maxScale"]) ? null : $configuration["maxScale"];
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
            ->setSelected(!isset($configuration["visible"]) ? true : $configuration["visible"])
            ->setPriority($num)
            ->setSourceItem($layersourceroot)
            ->setSourceInstance($this->entity)
            ->setToggle(!isset($configuration["toggle"]) ? false : $configuration["toggle"])
            ->setAllowtoggle(!isset($configuration["allowtoggle"]) ? true : $configuration["allowtoggle"])
            ->setPriority($num);
        $this->entity->addLayer($rootInstLayer);

        foreach ($configuration['layers'] as $layerDef) {
            $this->populateFromYaml($layerDef, $source, $layersourceroot, $rootInstLayer, $num++);
        }
        return $this->entity;
    }

    public function populateFromYaml($configuration, WmsSource $source, WmsLayerSource $layersource, WmsInstanceLayer $instancelayer, $num)
    {
            $num++;
            $childLayerSource = new WmsLayerSource();
            $childLayerSource->setSource($source)
                ->setName($configuration["name"])
                ->setTitle($configuration['title'])
                ->setParent($layersource)
                ->setId($layersource->getId() . '_' . $num);
            if (isset($configuration["legendurl"])) {
                $style          = new Style();
                $style->setName(null);
                $style->setTitle(null);
                $style->setAbstract(null);
                $legendUrl      = new LegendUrl();
                $legendUrl->setWidth(null);
                $legendUrl->setHeight(null);
                $onlineResource = new OnlineResource();
                $onlineResource->setFormat(null);
                $onlineResource->setHref($configuration["legendurl"]);
                $legendUrl->setOnlineResource($onlineResource);
                $style->setLegendUrl($legendUrl);
                $childLayerSource->addStyle($style);
            }
            $layersource->addSublayer($childLayerSource);
            $source->addLayer($childLayerSource);
            $childLayerInstance       = new WmsInstanceLayer();
            $childLayerInstance->setTitle($configuration["title"])
                ->setId($childLayerSource->getId())
                ->setMinScale(!isset($configuration["minScale"]) ? null : $configuration["minScale"])
                ->setMaxScale(!isset($configuration["maxScale"]) ? null : $configuration["maxScale"])
                ->setSelected(!isset($configuration["visible"]) ? true : $configuration["visible"])
                ->setInfo(!isset($configuration["queryable"]) ? false : $configuration["queryable"])
                ->setParent($instancelayer)
                ->setSourceItem($childLayerSource)
                ->setSourceInstance($this->entity)
                ->setAllowinfo($childLayerInstance->getInfo() !== null && $childLayerInstance->getInfo() ? true : false)
                ->setToggle(!isset($configuration["toggle"]) ? null : $configuration["toggle"])
                ->setAllowtoggle(!isset($configuration["allowtoggle"]) ? null : $configuration["allowtoggle"])
                ->setPriority($num);
            $instancelayer->addSublayer($childLayerInstance);
            $this->entity->addLayer($childLayerInstance);

            if (isset($configuration['layers'])) {
                foreach ($configuration['layers'] as $layerDef) {
                    $this->populateFromYaml($layerDef, $source, $childLayerSource, $childLayerInstance, $num++);
                }
            } else {
                $instancelayer->setToggle(false);
                $instancelayer->setAllowtoggle(true);
            }
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
        $entityManager = $this->getEntityManager();
        if ($this->entity->getRootlayer()) {
            $rootlayerSaveHandler = new WmsInstanceLayerEntityHandler($this->container, $this->entity->getRootlayer());
            $rootlayerSaveHandler->save();
        }
        $layerSet = $this->entity->getLayerset();
        $num = 0;
        foreach ($layerSet->getInstances() as $instance) {
            /** @var WmsInstance $instance */
            $instance->setWeight($num);
            $entityManager->persist($instance);
            $num++;
        }
        $application = $layerSet->getApplication();
        $application->setUpdated(new \DateTime('now'));
        $entityManager->persist($application);
        $entityManager->persist($this->entity);
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

        $rootUpdateHandler = new WmsInstanceLayerEntityHandler($this->container, $this->entity->getRootlayer());
        $rootUpdateHandler->update($this->entity, $this->entity->getSource()->getRootlayer());

        $entityManager = $this->getEntityManager();
        $application = $this->entity->getLayerset()->getApplication();
        $application->setUpdated(new \DateTime('now'));
        $entityManager->persist($application);
        $entityManager->persist($this->entity);
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
     * Returns ALL vendorspecific parameters, NOT just the hidden ones
     * @return string[]
     * @deprecated for bad wording, limited utility; last remaining use was in InnstanceTunnelService, which now
     *     handles this itself
     */
    public function getSensitiveVendorSpecific()
    {
        $handler = new VendorSpecificHandler();
        $token = $this->container->get('security.token_storage')->getToken();
        return $handler->getAllParams($this->entity, $token);
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
