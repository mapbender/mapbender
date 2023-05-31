<?php
    
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\Exchange\AbstractObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ExportDataPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\Exchange\ObjectHelper;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Paul Schmidt
 */
class ImportHandler extends ExchangeHandler
{
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var ExportHandler */
    protected $exportHandler;
    /** @var UploadsManager */
    protected $uploadsManager;
    /** @var MutableAclProviderInterface */
    protected $aclProvider;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ElementFilter $elementFilter
     * @param ExportHandler $exportHandler
     * @param UploadsManager $uploadsManager
     * @param MutableAclProviderInterface $aclProvider
     */
    public function __construct(EntityManagerInterface $entityManager,
                                ElementFilter $elementFilter,
                                ExportHandler $exportHandler,
                                UploadsManager $uploadsManager,
                                MutableAclProviderInterface $aclProvider)
    {
        parent::__construct($entityManager);
        $this->elementFilter = $elementFilter;
        $this->exportHandler = $exportHandler;
        $this->uploadsManager = $uploadsManager;
        $this->aclProvider = $aclProvider;
    }

    /**
     * @param array $data
     * @return Application[]
     * @throws ImportException
     */
    public function importApplicationData(array $data)
    {
        $entityPool = new EntityPool();
        $importState = new ImportState($this->em, $data, $entityPool);
        try {
            $this->importSources($importState, $data);
            $apps = $this->importApplicationEntities($importState, $data);
            if (!$apps) {
                throw new ImportException("No applications found");
            }
            foreach ($apps as $app) {
                // screenshot image is neither exported nor imported
                $app->setScreenshot(null);
                $this->em->persist($app);
            }
            $this->em->flush();

            return $apps;
        } catch (ORMException $e) {
            throw new ImportException("Database error {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @param Application $app
     * @param string|null $preferredSlug
     * @return Application
     * @throws ImportException
     */
    public function duplicateApplication(Application $app, $preferredSlug = null)
    {
        $originalSlug = $app->getSlug();
        $importPool = new EntityPool();
        if ($app->getSource() !== Application::SOURCE_YAML) {
            foreach ($app->getLayersets() as $layerset) {
                foreach ($layerset->getInstances(true) as $instance) {
                    $this->markSourceImported($importPool, $instance->getSource());
                    if (!$instance->getLayerset()) {
                        // shared instance, do not clone, keep referencing same object
                        $this->markEntityImported($importPool, $instance);
                    }
                }
            }
        } else {
            // Avoid saving an application clone to the db with the same slug
            // as the Yaml version. There's a unique constraint on the
            // database table, but it doesn't account for Yaml-defined
            // applications!
            $app->setSlug($app->getSlug() . '_db');
        }
        if ($preferredSlug) {
            $app->setSlug($preferredSlug);
        }

        $exportData = $this->exportHandler->exportApplication($app);
        $importState = new ImportState($this->em, $exportData, $importPool);
        try {
            if ($app->getSource() === Application::SOURCE_YAML) {
                $this->importSources($importState, $exportData);
            }
            $apps = $this->importApplicationEntities($importState, $exportData);
            if (count($apps) !== 1) {
                throw new ImportException("Logic error, no applications imported");
            }
            $clonedApp = $apps[0];
            $clonedApp->setScreenshot($app->getScreenshot());
            $this->em->persist($clonedApp);
            $this->uploadsManager->copySubdirectory($originalSlug, $clonedApp->getSlug());
            $this->em->flush();

            if ($app->getSource() !== Application::SOURCE_YAML) {
                $this->copyAcls($clonedApp, $app);
            }

            return $clonedApp;
        } catch (ORMException $e) {
            throw new ImportException("Database error {$e->getMessage()}", 0, $e);
        }
    }

    protected function markSourceImported(EntityPool $targetPool, Source $source)
    {
        foreach ($source->getLayers() as $layer) {
            $this->markEntityImported($targetPool, $layer);
        }
        $this->markEntityImported($targetPool, $source);
    }

    protected function markEntityImported(EntityPool $targetPool, $entity)
    {
        $eh = EntityHelper::getInstance($this->em, $entity);
        $targetPool->add($entity, $eh->extractIdentifier($entity));
    }

    /**
     * Extracts array from input. Supports json / yaml serialized string inputs and files.
     *
     * @param \SplFileInfo|array|string $data
     * @return array
     * @throws ImportException
     */
    public static function parseImportData($data)
    {
        if (!$data) {
            throw new \InvalidArgumentException("Invalid empty input data");
        } elseif (is_array($data)) {
            return $data;
        } elseif ($data instanceof \SplFileInfo) {
            return static::parseImportData(file_get_contents($data->getRealPath()));
        } elseif (is_string($data)) {
            try {
                $dec = json_decode($data, true);
                if ($dec === null && trim($data) !== json_encode(null)) {
                    throw new ImportException("Dummy text");
                }
                return $dec;
            } catch (\Exception $e) {
                try {
                    return Yaml::parse($data);
                } catch (\Exception $e) {
                    throw new ImportException("Input string could not be parsed into an array", 0, $e);
                }
            }
        } else {
            throw new \InvalidArgumentException("Invalid input type " . (is_object($data) ? get_class($data) : gettype($data)));
        }
    }

    /**
     * @param mixed $data
     * @return string|null
     */
    protected function extractClassName($data)
    {
        if (is_array($data) && array_key_exists(self::KEY_CLASS, $data)) {
            $className = $data[self::KEY_CLASS];
            if (is_array($className)) {
                $className = $className[0];
            }
            while (!empty($this->legacyClassMapping[$className])) {
                $className = $this->legacyClassMapping[$className];
            }
            return $className;
        }
        return null;
    }

    /**
     * @param array $data
     * @param string[] $fieldNames
     * @return array
     */
    protected function extractArrayFields(array $data, array $fieldNames)
    {
        return array_intersect_key($data, array_flip($fieldNames));
    }

    /**
     * Imports sources.
     * @param ImportState $state
     * @param array $data data to import
     */
    private function importSources(ImportState $state, $data)
    {
        foreach ($data as $class => $content) {
            if (is_a($class, 'Mapbender\CoreBundle\Entity\Source', true)) {
                foreach ($content as $item) {
                    if (!$this->findMatchingSource($state, $item)) {
                        $source = $this->handleData($state, $item);
                        $this->em->persist($source);
                        $this->em->flush();
                    }
                }
            }
        }
    }

    /**
     * @param ImportState $state
     * @param array $data data to import
     * @return Application[]
     */
    private function importApplicationEntities(ImportState $state, $data)
    {
        $apps = array();
        foreach ($data as $class => $content) {
            if (is_a($class, 'Mapbender\CoreBundle\Entity\Application', true)) {
                foreach ($content as $item) {
                    /** @var Application $app */
                    $app = $this->handleData($state, $item);
                    $app->setSource(Application::SOURCE_DB);
                    $app->setUpdated(new \DateTime());
                    $this->em->persist($app);
                    $newSlug = EntityUtil::getUniqueValue($this->em, 'MapbenderCoreBundle:Application', 'slug', $app->getSlug(), '_imp');
                    $newTitle = EntityUtil::getUniqueValue($this->em, 'MapbenderCoreBundle:Application', 'title', $app->getTitle(), '_imp');
                    $app->setSlug($newSlug);
                    $app->setTitle($newTitle);

                    // Flush once to generate Application and Element ids before Element
                    // configuration update.
                    $this->em->flush();
                    $this->updateElementConfiguration($state->getEntityPool(), $app);
                    // Flush again to store updated Element configuration
                    $this->em->flush();
                    $apps[] = $app;
                }
            }
        }
        return $apps;
    }

    /**
     * @param EntityPool $entityPool
     * @param Application $app
     */
    protected function updateElementConfiguration(EntityPool $entityPool, Application $app)
    {
        foreach ($app->getElements() as $element) {
            $configuration = $element->getConfiguration();
            if (!empty($configuration['target'])) {
                $newId = $entityPool->getIdentFromMapper(get_class($element), $configuration['target'], false);
                $configuration['target'] = $newId;
                $element->setConfiguration($configuration);
            }
            try {
                $this->elementFilter->migrateConfig($element);
                // allow Element service to fix relational data references post import (e.g. layerset ids on Map)
                $importProcessor = $this->elementFilter->getInventory()->getImportProcessor($element);
                if ($importProcessor) {
                    $importProcessor->onImport($element, $entityPool);
                }
            } catch (ElementErrorException $e) {
                // Likely an import from an application with custom element classes that are not defined here
                // => ignore, we still have the entity imported
            }
            $this->em->persist($element);
        }
    }

    /**
     * @param Application $target
     * @param Application $source
     * @throws InvalidDomainObjectException
     * @throws \Symfony\Component\Security\Acl\Exception\Exception
     */
    protected function copyAcls(Application $target, Application $source)
    {
        try {
            $sourceAcl = $this->aclProvider->findAcl(ObjectIdentity::fromDomainObject($source));
        } catch (AclNotFoundException $e) {
            // Nothing to copy
            return;
        }
        $targetOid = ObjectIdentity::fromDomainObject($target);
        try {
            $targetAcl = $this->aclProvider->createAcl($targetOid);
        } catch (AclAlreadyExistsException $e) {
            $targetAcl = $this->aclProvider->findAcl($targetOid);
        }

        foreach ($sourceAcl->getObjectAces() as $sourceEntry) {
            $entryIdentity = $sourceEntry->getSecurityIdentity();
            $targetAcl->insertObjectAce($entryIdentity, $sourceEntry->getMask());
        }
        $this->aclProvider->updateAcl($targetAcl);
    }

    /**
     * @param ImportState $state
     * @param array $data data to import
     * @return Source|null
     */
    private function findMatchingSource(ImportState $state, $data)
    {
        $className = $this->extractClassName($data);
        // Avoid inserting "new" sources that are duplicates of already existing ones
        // Finding equivalent sources is relatively expensive
        $identFields = array(
            'title',
            'type',
            'name',
            'onlineResource',
        );
        $criteria = $this->extractArrayFields($data, $identFields);
        foreach ($this->em->getRepository($className)->findBy($criteria) as $source) {
            $tempPool = new EntityPool();
            if ($this->compareSource($state, $tempPool, $source, $data)) {
                $classMeta = $this->em->getClassMetadata($className);
                $tempPool->add($source, $this->extractArrayFields($data, $classMeta->getIdentifier()));
                // Move references to Source and its layers over into the "already imported" set,
                // so no new entities will be created and persisted
                $state->getEntityPool()->merge($tempPool);
                return $source;
            }
        }
        return null;
    }

    /**
     * @param ImportState $state
     * @param EntityPool $entityPool
     * @param Source $source
     * @param array $data
     * @return bool
     */
    private function compareSource(ImportState $state, EntityPool $entityPool, $source, array $data)
    {
        foreach ($data['layers'] as $layerData) {
            $layerClass = $this->extractClassName($layerData);

            if (!$layerClass) {
                throw new ImportException("Missing source item class definition");
            }
            if (is_a($layerClass, 'Mapbender\WmsBundle\Entity\WmsLayerSource', true)) {
                $field = 'name';
            } elseif (is_a($layerClass, 'Mapbender\WmtsBundle\Entity\WmtsLayerSource', true)) {
                $field = 'identifier';
            } else {
                throw new ImportException("Unsupported layer type {$layerClass}");
            }
            $layerMeta = EntityHelper::getInstance($this->em, $layerClass)->getClassMeta();
            $layerIdentData = $this->extractArrayFields($layerData, $layerMeta->getIdentifier());
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

    protected function isReference($data, array $criteria)
    {
        return !array_diff_key($criteria, $data);
    }

    /**
     * @param ImportState $state
     * @param mixed $data
     * @return array|null|number|string|object
     */
    protected function handleData(ImportState $state, $data)
    {
        if ($className = $this->extractClassName($data)) {
            if ($entityInfo = EntityHelper::getInstance($this->em, $className)) {
                $identValues = $this->extractArrayFields($data, $entityInfo->getClassMeta()->getIdentifier());
                if ($object = $state->getEntityPool()->get($className, $identValues)) {
                    return $object;
                } else {
                    if ($this->isReference($data, $identValues)) {
                        $objectData = $state->getEntityData($className, $identValues);
                        if (!$objectData) {
                            return null;
                        }
                    } else {
                        $objectData = $data;
                    }
                    return $this->handleEntity($state, $entityInfo, $objectData);
                }
            } else {
                $classInfo = ObjectHelper::getInstance($className);
                return $this->handleClass($state, $classInfo, $data);
            }
        } elseif (is_array($data)) {
            $result = array();
            foreach ($data as $key => $item) {
                $result[$key] = $this->handleData($state, $item);
            }
            return $result;
        } elseif ($data === null || is_integer($data) || is_float($data) || is_string($data) || is_bool($data)) {
            return $data;
        } else {
            return null;
        }
    }

    /**
     * @param ExportDataPool $exportPool
     * @param $targetClassName
     * @param array $targetIdent
     * @return object|null
     */
    public function dehydrateExportObject(ExportDataPool $exportPool, $targetClassName, array $targetIdent)
    {
        $entityPool = new EntityPool();
        $importState = new ImportState($this->em, $exportPool->getAllGroupedByClassName(), $entityPool);
        $this->handleData($importState, $importState->getEntityData($targetClassName, $targetIdent));
        return $entityPool->get($targetClassName, $targetIdent);
    }

    /**
     * @param ImportState $state
     * @param EntityHelper $entityInfo
     * @param array $data
     * @return object|null
     */
    protected function handleEntity(ImportState $state, EntityHelper $entityInfo, array $data)
    {
        $classMeta = $entityInfo->getClassMeta();
        $className = $classMeta->getName();
        $identFieldNames = $classMeta->getIdentifier();

        $setters = $entityInfo->getSetters();
        $object = new $className();
        $nonIdentifierFieldNames = array_diff($classMeta->getFieldNames(), $identFieldNames);
        foreach ($nonIdentifierFieldNames as $fieldName) {
            if (isset($data[$fieldName]) && array_key_exists($fieldName, $setters)) {
                $setter = $setters[$fieldName];
                $value = $this->handleData($state, $data[$fieldName]);
                $setter->invoke($object, $value);
            }
        }

        $this->em->persist($object);
        $state->getEntityPool()->add($object, $this->extractArrayFields($data, $identFieldNames));

        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            if ($this->isEntityClassBlacklisted($assocItem['targetEntity'])) {
                continue;
            }
            $assocFieldName = $assocItem['fieldName'];
            if (array_key_exists($assocFieldName, $setters) && isset($data[$assocFieldName])) {
                $setter = $setters[$assocFieldName];
                $result = $this->handleData($state, $data[$assocItem['fieldName']]);
                if (is_array($result)) {
                    if (count($result)) {
                        $collection = new \Doctrine\Common\Collections\ArrayCollection($result);
                        $setter->invoke($object, $collection);
                    }
                } else {
                    $setter->invoke($object, $result);
                }
                $this->em->persist($object);
            }
        }
        return $object;
    }

    /**
     * @param ImportState $state
     * @param AbstractObjectHelper $classInfo
     * @param array $data
     * @return object
     */
    protected function handleClass(ImportState $state, AbstractObjectHelper $classInfo, array $data)
    {
        $className = $classInfo->getClassName();
        $object = new $className();
        foreach ($classInfo->getSetters(array_keys($data)) as $propertyName => $setter) {
            if ($data[$propertyName] !== null) {
                // support hack for older exports without support for nested non-entity objects
                if (empty($data[$propertyName][static::KEY_CLASS])) {
                    if ($subObjectClassName = $this->getLegacyExportMissingSubobjectClassName($className, $setter->getName())) {
                        $data[$propertyName][static::KEY_CLASS] = array(
                            $subObjectClassName,
                        );
                    }
                }
                $value = $this->handleData($state, $data[$propertyName]);
                if (!is_array($value) || count($value)) {
                    $setter->invoke($object, $value);
                }
            }
        }
        return $object;
    }

    /**
     * @param Application $application
     * @param SecurityIdentityInterface $sid
     */
    public function addOwner(Application $application, SecurityIdentityInterface $sid)
    {
        $oid = ObjectIdentity::fromDomainObject($application);
        try {
            $acl = $this->aclProvider->createAcl($oid);
        } catch (AclAlreadyExistsException $e) {
            $acl = $this->aclProvider->findAcl($oid);
        }
        /** @var \Symfony\Component\Security\Acl\Domain\Entry[] $aces */
        $aces = $acl->getObjectAces();
        $updatedExistingAce = false;
        foreach ($aces as $index => $ace) {
            if ($ace->getSecurityIdentity()->equals($sid)) {
                $acl->updateObjectAce($index, $ace->getMask() | MaskBuilder::MASK_OWNER);
                $updatedExistingAce = true;
                break;
            }
        }
        if (!$updatedExistingAce) {
            $acl->insertObjectAce($sid, MaskBuilder::MASK_OWNER);
        }
        $this->aclProvider->updateAcl($acl);
    }

    /**
     * Check for class names of nested non-entity objects where legacy export
     * didn't emit the correct class signature.
     *
     * @param string $className
     * @param string $setterName
     * @return string|null
     */
    protected function getLegacyExportMissingSubobjectClassName($className, $setterName)
    {
        switch ($setterName) {
            case 'setLegendUrl':
                if (\is_a($className, 'Mapbender\WmsBundle\Component\Style', true)) {
                    return 'Mapbender\WmsBundle\Component\LegendUrl';
                }
                break;
            case 'setOnlineResource':
                if (\is_a($className, 'Mapbender\WmsBundle\Component\LegendUrl', true) ||
                    \is_a($className, 'Mapbender\WmsBundle\Component\MetadataUrl', true)) {
                    return 'Mapbender\WmsBundle\Component\OnlineResource';
                }
                break;
            default:
                // nothing
                break;
        }
        return null;
    }
}
