<?php


namespace Mapbender\WmtsBundle\Component;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Mapbender\ManagerBundle\Form\Model\HttpOriginModel;
use Mapbender\WmtsBundle\Component\Wmts\Loader;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;

abstract class InstanceFactoryCommon extends SourceInstanceFactory
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected Loader                 $loader,
    )
    {
    }

    public function createInstance(Source $source, ?array $options = null): WmtsInstance
    {
        /** @var WmtsSource $source */
        $instance = new WmtsInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());

        $rootLayer = null;
        foreach ($source->getLayers() as $layer) {
            $instLayer = $this->createInstanceLayer($layer, $rootLayer);
            if ($layer->getParent() === null) $rootLayer = $instLayer;
            $instance->addLayer($instLayer);
        }
        // avoid persistence errors (non-nullable column)
        $instance->setWeight(0);
        return $instance;
    }

    public static function createInstanceLayer(WmtsLayerSource $sourceLayer, ?WmtsInstanceLayer $parent = null)
    {
        $instanceLayer = new WmtsInstanceLayer();
        $instanceLayer->setSourceItem($sourceLayer);
        $instanceLayer->setTitle($sourceLayer->getTitle());
        $instanceLayer->setPriority($sourceLayer->getPriority());
        $instanceLayer->setAllowtoggle($sourceLayer->getParent() === null);
        $instanceLayer->setToggle($sourceLayer->getParent() === null);
        $instanceLayer->setParent($parent);
        return $instanceLayer;
    }

    public function fromConfig(array $data, string $id): SourceInstance
    {
        $formData = new HttpOriginModel();
        $formData->setOriginUrl($data['url'] ?? null);
        $formData->setPassword($data['password'] ?? null);
        $formData->setUsername($data['username'] ?? null);
        /** @var WmtsSource $source */
        $source = $this->loader->loadSource($formData);
        $source->setId($id);

        $instance = $this->createInstance($source);
        $instance->setId($id);
        $instance->setBasesource($data['basesource'] ?? $data['isBaseSource'] ?? false);
        $instance->setOpacity($data['opacity'] ?? 100);
        $instance->setProxy($data['proxy'] ?? false);

        $hasLayerInfo = isset($data['layers']) && is_array($data['layers']);

        // Layer hierarchy is usually created by the database, do it manually for YAML applications
        // also, apply layer settings from the YAML config
        foreach ($instance->getLayers() as $layer) {
            if ($hasLayerInfo) {
                $this->applyLayerDataFromYaml($data, $layer);
            }

            if ($layer->getParent() !== null) {
                $layer->getParent()->addSublayer($layer);
                $instance->removeLayer($layer);
            }
        }
        // set ids (also usually handled by the database)
        $layerIndex = 0;
        foreach ($instance->getLayers() as $layer) {
            $this->setLayerId($layer, "{$id}_$layerIndex");
            $layerIndex++;
        }
        $tmsIndex = 0;
        foreach($source->getTilematrixsets() as $set) {
            $set->setId("{$id}_$tmsIndex");
            $tmsIndex++;
        }
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
        foreach ($this->entityManager->getRepository(WmtsSource::class)->findBy($criteria) as $source) {
            if ($this->compareSource($importState, $entityPool, $source, $data)) {
                $classMeta = $this->entityManager->getClassMetadata(WmtsSource::class);
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
            if (is_a($layerClass, 'Mapbender\WmtsBundle\Entity\WmtsLayerSource', true)) {
                $field = 'identifier';
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

    private function setLayerId(WmtsInstanceLayer $layer, string $id): void
    {
        $layer->setId($id);
        $layer->getSourceItem()->setId($id);
        $subLayerIndex = 0;
        foreach ($layer->getSublayer() as $subLayer) {
            $this->setLayerId($subLayer, "{$id}_$subLayerIndex");
            $subLayerIndex++;
        }
    }

    private function applyLayerDataFromYaml(array $data, mixed $layer): void
    {
        if (!isset($data['layers'][$layer->getSourceItem()->getIdentifier()])) {
            return;
        }
        $layerData = $data['layers'][$layer->getSourceItem()->getIdentifier()];

        if (isset($layerData['title'])) {
            $layer->setTitle($layerData['title']);
        }
        if (isset($layerData['priority'])) {
            $layer->setPriority($layerData['priority']);
        }
        $layer->setToggle($layerData['toggle'] ?? true);
        $layer->setAllowtoggle($layerData['allowToggle'] ?? true);
        $layer->setSelected($layerData['selected'] ?? true);
        $layer->setActive($layerData['active'] ?? true);
        $layer->setAllowselected($layerData['allowSelected'] ?? true);
        $layer->setAllowinfo($layerData['allowInfo'] ?? true);
        $layer->setInfo($layerData['info'] ?? true);
    }
}
