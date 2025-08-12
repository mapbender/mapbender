<?php


namespace Mapbender\WmsBundle\Component\Wms;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\WmsBundle\Component\LegendUrl;
use Mapbender\WmsBundle\Component\MinMax;
use Mapbender\WmsBundle\Component\OnlineResource;
use Mapbender\WmsBundle\Component\RequestInformation;
use Mapbender\WmsBundle\Component\Style;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Mapbender\WmsBundle\Entity\WmsLayerSource;
use Mapbender\WmsBundle\Entity\WmsSource;

class SourceInstanceFactory extends \Mapbender\CoreBundle\Component\Source\SourceInstanceFactory
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected ?string                $defaultLayerOrder,
    )
    {
    }

    public function createInstance(Source $source, ?array $options = null): WmsInstance
    {
        /** @var WmsSource $source $instance */
        $instance = new WmsInstance();
        $instance->setSource($source);
        $instance->populateFromSource($source);

        if ($this->defaultLayerOrder) {
            $instance->setLayerOrder($this->defaultLayerOrder);
        }
        // avoid persistence errors (non-nullable column)
        $instance->setWeight(0);

        if ($options) {
            if (!empty($options['format']) && in_array($options['format'], $source->getGetMap()->getFormats())) {
                $instance->setFormat($options['format']);
            }
            if (!empty($options['infoformat']) && in_array($options['infoformat'], $source->getGetFeatureInfo()->getFormats())) {
                $instance->setInfoFormat($options['infoformat']);
            }
            if (!empty($options['proxy']) && $options['proxy'] === 'true') {
                $instance->setProxy(true);
            }
            if (!empty($options['tiled']) && $options['tiled'] === 'true') {
                $instance->setTiled(true);
            }
            if (!empty($options['layerorder']) && in_array($options['layerorder'], ['standard', 'reverse'])) {
                $instance->setLayerOrder($options['layerorder']);
            }
        }
        return $instance;
    }

    /**
     * @param array $data
     * @param string $id used for instance and as instance layer id prefix
     * @return WmsInstance
     */
    public function fromConfig(array $data, string $id): WmsInstance
    {
        $source = $this->sourceFromConfig($data, $id);
        $instance = $this->createInstance($source, null);
        $instance->setId($id);
        $instance
            ->setTitle(ArrayUtil::getDefault($data, 'title', $source->getTitle()))
            ->setProxy(!isset($data['proxy']) ? false : $data['proxy'])
            ->setFormat(!isset($data['format']) ? 'image/png' : $data['format'])
            ->setInfoformat(!isset($data['info_format']) ? 'text/html' : $data['info_format'])
            ->setTransparency(!isset($data['transparent']) ? true : $data['transparent'])
            ->setOpacity(!isset($data['opacity']) ? 100 : $data['opacity'])
            ->setTiled(!isset($data['tiled']) ? false : $data['tiled'])
            ->setBasesource(!isset($data['isBaseSource']) ? true : $data['isBaseSource'])
        ;
        if (!empty($data['layerorder'])) {
            $instance->setLayerOrder($data['layerorder']);
        }
        $this->configureInstanceLayer($instance->getRootlayer(), $data);
        return $instance;
    }

    public function matchInstanceToPersistedSource(ImportState $importState, array $data, EntityPool $entityPool): bool
    {
        $identFields = array(
            'title',
            'type',
            'name',
            'onlineResource',
        );
        $criteria = ImportHandler::extractArrayFields($data, $identFields);
        foreach ($this->entityManager->getRepository(WmsSource::class)->findBy($criteria) as $source) {
            if ($this->compareSource($importState, $entityPool, $source, $data)) {
                $classMeta = $this->entityManager->getClassMetadata(WmsSource::class);
                $entityPool->add($source, ImportHandler::extractArrayFields($data, $classMeta->getIdentifier()));
                return true;
            }
        }
        return false;
    }

    private function compareSource(ImportState $state, EntityPool $entityPool, $source, array $data)
    {
        foreach ($data['layers'] as $layerData) {
            $layerClass = ImportHandler::extractClassName($layerData);

            if (!$layerClass) {
                throw new ImportException("Missing source item class definition");
            }
            if (is_a($layerClass, 'Mapbender\WmsBundle\Entity\WmsLayerSource', true)) {
                $field = 'name';
            } else {
                throw new ImportException("Unsupported layer type {$layerClass}");
            }
            $layerMeta = EntityHelper::getInstance($this->entityManager, $layerClass)->getClassMeta();
            $layerIdentData = ImportHandler::extractArrayFields($layerData, $layerMeta->getIdentifier());
            $layerData = $state->getEntityData($layerClass, $layerIdentData) ?: $layerData;

            $criteria = Criteria::create()->where(Criteria::expr()->eq($field, $layerData[$field]));
            $match = $source->getLayers()->matching($criteria)->first();
            if ($match) {
                $entityPool->add($match, $layerIdentData);
            } else {
                return false;
            }
        }
        return true;
    }

    protected function configureInstanceLayer(WmsInstanceLayer $instanceLayer, array $data)
    {
        $instanceLayer
            ->setId($instanceLayer->getSourceItem()->getId())
            ->setSelected(!isset($data["visible"]) ? true : $data["visible"])
            ->setInfo(!isset($data["queryable"]) ? false : $data["queryable"], true)
            ->setAllowinfo($instanceLayer->getInfo() !== null && $instanceLayer->getInfo())
            ->setToggle(ArrayUtil::getDefault($data, 'toggle', $instanceLayer->getParent() ? null : false))
            ->setAllowtoggle(ArrayUtil::getDefault($data, 'allowtoggle', $instanceLayer->getSourceItem()->getSublayer()->count() ? true : null))
        ;

        if (!empty($data['layers'])) {
            $instanceLayers = $instanceLayer->getSublayer()->getValues();
            foreach (array_values($data['layers']) as $childIndex => $childLayerData) {
                $this->configureInstanceLayer($instanceLayers[$childIndex], $childLayerData);
            }
        }
    }

    protected function sourceFromConfig(array $data, string $id): WmsSource
    {
        $source = new WmsSource();
        $source
            ->setId($id)
            ->setTitle(ArrayUtil::getDefault($data, 'title', $id))
            ->setVersion(!isset($data['version']) ? '1.1.1' : $data['version'])
            ->setOriginUrl(!isset($data['url']) ? null : $data['url'])
        ;
        $getMap = new RequestInformation();
        $getMap->addFormat(!isset($data['format']) ? 'image/png' : $data['format']);
        // @todo: empty url is an error condition
        $getMap->setHttpGet(!isset($data['url']) ? null : $data['url']);
        $source->setGetMap($getMap);
        if (isset($data['info_format'])) {
            $getFeatureInfo = new RequestInformation();
            $getFeatureInfo->addFormat(!isset($data['info_format']) ? 'text/html' : $data['info_format']);
            // @todo: empty url is an error condition
            $getFeatureInfo->setHttpGet(!isset($data['url']) ? null : $data['url']);
            $source->setGetFeatureInfo($getFeatureInfo);
        }
        $this->rootLayerFromConfig($source, $data);
        return $source;
    }

    protected function rootLayerFromConfig(WmsSource $source, array $data): WmsLayerSource
    {
        return $this->layerFromConfig($source, $data, null);
    }

    protected function layerFromConfig(WmsSource $source, array $data, ?WmsLayerSource $parent = null, int $order = 0): WmsLayerSource
    {
        $layer = new WmsLayerSource();
        $minScale = $data["minScale"] ?? null;
        $maxScale = $data["maxScale"] ?? null;
        $layer
            ->setPriority($order)
            ->setSource($source)
            ->setScale(new MinMax($minScale, $maxScale))
            ->setTitle($data['title'] ?? '')
        ;
        if (!empty($data['name'])) {
            $layer->setName($data['name']);
        }
        if (!empty($data["legendurl"])) {
            $style = new Style();
            $style->setName(null);
            $style->setTitle(null);
            $style->setAbstract(null);
            $onlineResource = new OnlineResource();
            $onlineResource->setFormat(null);
            $onlineResource->setHref($data["legendurl"]);
            $legendUrl = new LegendUrl($onlineResource);
            $style->setLegendUrl($legendUrl);
            $layer->addStyle($style);
        }

        if ($parent) {
            $layer->setId($parent->getId() . '_' . $order);
            $parent->addSublayer($layer);
        } else {
            $layer->setId($source->getId() . '_' . $order);
        }
        $source->addLayer($layer);
        if (!empty($data['layers'])) {
            foreach (array_values($data['layers']) as $childOrder => $layerDef) {
                $this->layerFromConfig($source, $layerDef, $layer, $childOrder);
            }
        }
        return $layer;
    }

    public function canDeactivateLayer(SourceInstanceItem $instanceItem): bool
    {
        /** @var WmsInstanceLayer $instanceItem */
        // disallow breaking entire instance by removing root layer
        return $instanceItem->getSourceInstance()->getRootlayer() !== $instanceItem;
    }

    public function getFormType(SourceInstance $instance): string
    {
        return 'Mapbender\WmsBundle\Form\Type\WmsInstanceInstanceLayersType';
    }

    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderWms/Repository/instance.html.twig';
    }
}
