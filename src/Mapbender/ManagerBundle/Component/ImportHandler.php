<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use FOM\UserBundle\Component\AclManager;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Utils\EntityUtil;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Component\Exchange\AbstractObjectHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityHelper;
use Mapbender\ManagerBundle\Component\Exchange\EntityPool;
use Mapbender\ManagerBundle\Component\Exchange\ImportState;
use Mapbender\ManagerBundle\Component\Exchange\ObjectHelper;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Yaml\Yaml;

/**
 * Description of ImportHandler
 *
 * @author Paul Schmidt
 */
class ImportHandler extends ExchangeHandler
{
    /** @var ElementFactory */
    protected $elementFactory;
    /** @var MutableAclProviderInterface */
    protected $aclProvider;
    /** @var AclManager */
    protected $aclManager;

    /**
     * @inheritdoc
     */
    public function __construct(EntityManagerInterface $entityManager,
                                ElementFactory $elementFactory,
                                MutableAclProviderInterface $aclProvider,
                                AclManager $aclManager)
    {
        parent::__construct($entityManager);
        $this->elementFactory = $elementFactory;
        $this->aclProvider = $aclProvider;
        $this->aclManager = $aclManager;
    }

    /**
     * @param array $data
     * @param bool $copyHint set to true to enable optimizations for a same-db / same-id-space scenario
     * @return Application[]
     * @throws ImportException
     */
    public function importApplicationData(array $data, $copyHint=false)
    {
        $entityPool = new EntityPool();
        $importState = new ImportState($this->em, $data, $entityPool);
        try {
            $this->importSources($importState, $data, $copyHint);
            $apps = $this->importApplicationEntities($importState, $data);
            if (!$apps) {
                throw new ImportException("No applications found");
            }
            $this->em->flush();

            return $apps;
        } catch (ORMException $e) {
            throw new ImportException("Database error {$e->getMessage()}", 0, $e);
        }
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
     * @param bool $copyHint
     * @throws ORMException
     */
    private function importSources(ImportState $state, $data, $copyHint)
    {
        foreach ($data as $class => $content) {
            if (is_a($class, 'Mapbender\CoreBundle\Entity\Source', true)) {
                foreach ($content as $item) {
                    if (!$this->findMatchingSource($state, $item, $copyHint)) {
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
     * @throws ORMException
     */
    private function importApplicationEntities(ImportState $state, $data)
    {
        $apps = array();
        foreach ($data as $class => $content) {
            if (is_a($class, 'Mapbender\CoreBundle\Entity\Application', true)) {
                foreach ($content as $item) {
                    /** @var Application $app */
                    $app = $this->handleData($state, $item);
                    $app->setScreenshot(null)->setSource(Application::SOURCE_DB);
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
     * @param \Mapbender\CoreBundle\Entity\Application $app
     */
    protected function updateElementConfiguration(EntityPool $entityPool, Application $app)
    {
        foreach ($app->getElements() as $element) {
            $configuration = $element->getConfiguration();
            if (!empty($configuration['target'])) {
                $newId = $entityPool->getIdentFromMapper(get_class($element), $configuration['target'], false);
                $configuration['target'] = $newId;
            }
            try {
                // allow Component\Element to fix relational data references post import (e.g. layerset ids on Map)
                $elmComp = $this->elementFactory->componentFromEntity($element);
                $configuration = $elmComp->denormalizeConfiguration($configuration, $entityPool);
            } catch (ElementErrorException $e) {
                // Likely an import from an application with custom element classes that are not defined here
                // => ignore, we still have the entity imported
            }
            $element->setConfiguration($configuration);
            $this->em->persist($element);
        }
    }

    /**
     * @param Application $application
     * @param mixed $currentUser
     */
    public function setDefaultAcls(Application $application, $currentUser)
    {
        $aces = array(
            array(
                'sid' => UserSecurityIdentity::fromAccount($currentUser),
                'mask' => MaskBuilder::MASK_OWNER
            ),
        );
        $this->aclManager->setObjectACL($application, $aces, 'object');
    }

    /**
     * @param Application $target
     * @param Application $source
     * @param $currentUser
     * @throws InvalidDomainObjectException
     * @throws \Symfony\Component\Security\Acl\Exception\Exception
     */
    public function copyAcls(Application $target, Application $source, $currentUser)
    {
        $this->setDefaultAcls($target, $currentUser);
        $sourceAcl = $this->aclProvider->findAcl(ObjectIdentity::fromDomainObject($source));
        /** @var MutableAclInterface $targetAcl */
        $targetAcl = $this->aclManager->getACL($target);
        $currentUserIdentity = UserSecurityIdentity::fromAccount($currentUser);

        // Quirky old behavior: current user has just been given MASK_OWNER, so access for that user
        // is as complete as it can get. We only copy aces for other user identities. Any other
        // entries for the current user are ignored and not copied over.
        // We also only copy the identity + mask portions of the Aces, nothing else.
        // @todo: Figure out if this really is the desired behaviour going forward.
        foreach ($sourceAcl->getObjectAces() as $sourceEntry) {
            /** @var EntryInterface $sourceEntry */
            $entryIdentity = $sourceEntry->getSecurityIdentity();
            if (!$currentUserIdentity->equals($entryIdentity)) {
                $targetAcl->insertObjectAce($entryIdentity, $sourceEntry->getMask());
            }
        }
        $this->aclProvider->updateAcl($targetAcl);
    }

    /**
     * @param ImportState $state
     * @param array $data data to import
     * @param bool $copyHint
     * @return Source|null
     */
    private function findMatchingSource(ImportState $state, $data, $copyHint)
    {
        $className = $this->extractClassName($data);
        if (!$copyHint) {
            // Avoid inserting "new" sources that are duplicates of already existing ones
            // Finding equivalent sources is relatively expensive
            $identFields = array(
                'title',
                'type',
                'name',
                'onlineResource',
            );
        } else {
            // Performance hack: when "importing" from the same DB (actually just copying an
            // application), detecting source duplicates based purely on id is sufficient
            $classMeta = $this->em->getClassMetadata($className);
            $identFields = $classMeta->getIdentifier();
        }
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
     * @throws \Doctrine\ORM\ORMException
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
     * @param ImportState $state
     * @param EntityHelper $entityInfo
     * @param array $data
     * @return object|null
     * @throws \Doctrine\ORM\ORMException
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
     * @throws \Doctrine\ORM\ORMException
     */
    protected function handleClass(ImportState $state, AbstractObjectHelper $classInfo, array $data)
    {
        $className = $classInfo->getClassName();
        $object = new $className();
        foreach ($classInfo->getSetters(array_keys($data)) as $propertyName => $setter) {
            if ($data[$propertyName] !== null) {
                $value = $this->handleData($state, $data[$propertyName]);
                if (!is_array($value) || count($value)) {
                    $setter->invoke($object, $value);
                }
            }
        }
        return $object;
    }
}
