<?php


namespace Mapbender\WmtsBundle\Component;


use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Component\Source\SourceInstanceFactory;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\ImportHandler;

abstract class InstanceFactoryCommon extends SourceInstanceFactory
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
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
        throw new \RuntimeException("Yaml-defined Wmts sources not implemented");
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
}
