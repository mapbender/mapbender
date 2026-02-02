<?php

namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\Permission\PermissionManager;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\SubjectDomainPublic;
use FOM\UserBundle\Security\Permission\SubjectDomainRegistered;
use FOM\UserBundle\Security\Permission\YamlApplicationVoter;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Component\Source\TypeDirectoryService;
use Mapbender\CoreBundle\Component\UploadsManager;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\Exchange\AbstractObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\Exchange\ObjectHelper;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Paul Schmidt
 */
class ImportHandler extends ExchangeHandler
{

    public function __construct(EntityManagerInterface                  $entityManager,
                                protected ElementFilter                 $elementFilter,
                                protected ExportHandler                 $exportHandler,
                                protected UploadsManager                $uploadsManager,
                                protected PermissionManager             $permissionManager,
                                protected readonly TypeDirectoryService $typeDirectoryService
    )
    {
        parent::__construct($entityManager);
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

            $this->duplicatePermissions($importState, $app);
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

    public static function extractClassName($data): ?string
    {
        if (is_array($data) && array_key_exists(self::KEY_CLASS, $data)) {
            $className = $data[self::KEY_CLASS];
            if (is_array($className)) {
                $className = $className[0];
            }
            while (!empty(self::$legacyClassMapping[$className])) {
                $className = self::$legacyClassMapping[$className];
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
    public static function extractArrayFields(array $data, array $fieldNames)
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
                    $newSlug = EntityUtil::getUniqueValue($this->em, Application::class, 'slug', $app->getSlug(), '_imp');
                    $newTitle = EntityUtil::getUniqueValue($this->em, Application::class, 'title', $app->getTitle(), '_imp');
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

    private function findMatchingSource(ImportState $state, array $data): bool
    {
        $instanceFactory = $this->typeDirectoryService->getInstanceFactoryByType($data['type']);
        $entityPool = new EntityPool();
        $hasMatch = $instanceFactory->matchInstanceToPersistedSource($state, $data, $entityPool);

        if ($hasMatch) {
            $state->getEntityPool()->merge($entityPool);
        }

        return $hasMatch;
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
        if ($className = self::extractClassName($data)) {
            if ($entityInfo = EntityHelper::getInstance($this->em, $className)) {
                $identValues = self::extractArrayFields($data, $entityInfo->getClassMeta()->getIdentifier());
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
        $state->getEntityPool()->add($object, self::extractArrayFields($data, $identFieldNames));

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

    public function addOwner(Application $application, UserInterface $user): void
    {
        $this->permissionManager->grant($user, $application, ResourceDomainApplication::ACTION_MANAGE_PERMISSIONS);
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

    protected function setupPermissionsFromYaml(
        Application|Element $sourceYamlResource,
        Application|Element $targetDbResource,
        string              $action = ResourceDomainApplication::ACTION_VIEW
    ): void
    {
        foreach ($sourceYamlResource->getYamlRoles() as $key => $role) {
            if ($role === YamlApplicationVoter::ROLE_PUBLIC) {
                $this->permissionManager->grant(SubjectDomainPublic::SLUG, $targetDbResource, $action);
            }
            if ($role === YamlApplicationVoter::ROLE_REGISTERED) {
                $this->permissionManager->grant(SubjectDomainRegistered::SLUG, $targetDbResource, $action);
            }
            if (($key === YamlApplicationVoter::USERS || $key === YamlApplicationVoter::GROUPS) && is_array($role)) {
                $this->addUserRightsFromYaml($targetDbResource, $key, $role, $action);
            }

            if (is_array($role)) {
                foreach ($role as $innerKey => $children) {
                    if (is_array($children)) $this->addUserRightsFromYaml($targetDbResource, $innerKey, $children, $action);
                }
            }
        }
    }

    protected function addUserRightsFromYaml(
        Application|Element $resource,
        string              $key,
        array               $children,
        string              $action = ResourceDomainApplication::ACTION_VIEW
    ): void
    {
        if ($key === YamlApplicationVoter::USERS) {
            $users = $this->em->getRepository(User::class)->findBy(['username' => $children]);
            foreach ($users as $user) {
                $this->permissionManager->grant($user, $resource, $action);
            }
        }

        if ($key === YamlApplicationVoter::GROUPS) {
            $groups = $this->em->getRepository(Group::class)->findBy(['title' => $children]);
            foreach ($groups as $group) {
                $this->permissionManager->grant($group, $resource, $action);
            }
        }
    }

    protected function duplicatePermissions(ImportState $importState, Application $app): void
    {
        $entityArray = [
            $app,
            ...$app->getElements(),
        ];

        foreach ($entityArray as $sourceEntity) {
            $targetEntity = $importState->getEntityPool()->getMappedEntity($sourceEntity::class, $sourceEntity->getId(), false);
            if (!$targetEntity) continue;
            if ($app->getSource() === Application::SOURCE_YAML) {
                $this->setupPermissionsFromYaml($sourceEntity, $targetEntity);
            } elseif ($app->getSource() === Application::SOURCE_DB) {
                $this->permissionManager->copyPermissions($sourceEntity, $targetEntity);
            }
        }
    }
}
