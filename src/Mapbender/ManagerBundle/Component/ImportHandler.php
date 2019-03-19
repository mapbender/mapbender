<?php
namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;
use Doctrine\ORM\PersistentCollection;
use FOM\UserBundle\Component\AclManager;
use Mapbender\CoreBundle\Component\ElementFactory;
use Mapbender\CoreBundle\Component\Exception\ElementErrorException;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\ManagerBundle\Component\Exception\ImportException;
use Mapbender\ManagerBundle\Form\Type\ImportJobType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\InvalidDomainObjectException;
use Symfony\Component\Security\Acl\Model\EntryInterface;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Yaml\Exception\ParseException;
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
                                AclManager $aclManager,
                                FormFactoryInterface $formFactory)
    {
        parent::__construct($entityManager, $formFactory);
        $this->elementFactory = $elementFactory;
        $this->aclProvider = $aclProvider;
        $this->aclManager = $aclManager;
    }

    /**
     * @inheritdoc
     */
    public function createForm()
    {
        $type = new ImportJobType();
        $data = new ImportJob();
        return $this->formFactory->create($type, $data);
    }

    /**
     * @param array $data
     * @param bool $copyHint set to true to enable optimizations for a same-db / same-id-space scenario
     * @return Application[]
     * @throws ImportException
     */
    public function importApplicationData(array $data, $copyHint=false)
    {
        $denormalizer = new ExchangeDenormalizer($this->em, $data);
        try {
            $this->importSources($denormalizer, $data, $copyHint);
            $apps = $this->importApplicationEntities($denormalizer, $data);
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
                return Yaml::parse($data);
            } catch (ParseException $e) {
                $dec = json_decode($data, true);
                if ($dec === null && trim($data) !== json_encode(null)) {
                    throw new ImportException("Input string could not be parsed into an array", 0, $e);
                }
                return $dec;
            }
        } else {
            throw new \InvalidArgumentException("Invalid input type " . (is_object($data) ? get_class($data) : gettype($data)));
        }
    }

    /**
     * Imports sources.
     * @param ExchangeDenormalizer $denormalizer
     * @param array $data data to import
     * @param bool $copyHint
     * @throws ImportException
     * @throws ORMException
     */
    private function importSources($denormalizer, $data, $copyHint)
    {
        foreach ($data as $class => $content) {
            if (is_a($class, 'Mapbender\CoreBundle\Entity\Source', true)) {
                foreach ($content as $item) {
                    $classMeta = $this->em->getClassMetadata($class);
                    if (!$copyHint) {
                        // Avoid inserting "new" sources that are duplicates of already existing ones
                        // Finding equivalent sources is relatively expensive
                        $identFields = $denormalizer->collectEntityFieldNames($classMeta, false, true, array(
                            'title',
                            'type',
                            'name',
                            'onlineResource',
                        ));
                        $criteria = $denormalizer->extractFields($item, $identFields);
                        $sources = $this->em->getRepository($class)->findBy($criteria);
                        if (!$this->findSourceToMapper($denormalizer, $sources, $item)) {
                            $source = $denormalizer->handleData($item);
                            $this->em->persist($source);
                            $this->em->flush();
                        }
                    } else {
                        // Performance hack: when "importing" from the same DB (actually just copying an
                        // application), detecting source duplicates based purely on id is sufficient
                        $criteria = $denormalizer->getIdentCriteria($item, $classMeta);
                        $sources = $this->em->getRepository($class)->findBy($criteria);
                        $this->addSourceToMapper($denormalizer, $sources[0], $item);
                    }
                }
            }
        }
    }

    /**
     * @param ExchangeDenormalizer $denormalizer
     * @param array $data data to import
     * @return Application[]
     * @throws ORMException
     */
    private function importApplicationEntities($denormalizer, $data)
    {
        $apps = array();
        foreach ($data as $class => $content) {
            if (is_a($class, 'Mapbender\CoreBundle\Entity\Application', true)) {
                foreach ($content as $item) {
                    /** @var Application $app */
                    $app = $denormalizer->handleData($item);
                    $app->setScreenshot(null)->setSource(Application::SOURCE_DB);
                    $this->em->persist($app);
                    $this->updateElementConfiguration($denormalizer, $app);
                    $apps[] = $app;
                    $this->em->persist($app);
                    $this->em->flush();
                }
            }
        }
        return $apps;
    }

    /**
     * @param ExchangeDenormalizer $denormalizer
     * @param \Mapbender\CoreBundle\Entity\Application $app
     */
    protected function updateElementConfiguration($denormalizer, Application $app)
    {
        foreach ($app->getElements() as $element) {
            $configuration = $element->getConfiguration();
            foreach ($configuration as $key => $value) {
                if ($key === 'target') {
                    $realClass = $denormalizer->getRealClass($element);
                    $target = $denormalizer->getAfterFromBefore($realClass, array('id' => $value));
                    $configuration[$key] = $target['criteria']['id'];
                } else {
                    $configuration[$key] = $value;
                }
            }
            try {
                // allow Component\Element to fix relational data references post import (e.g. layerset ids on Map)
                $elmComp = $this->elementFactory->componentFromEntity($element);
                $configuration = $elmComp->denormalizeConfiguration($configuration, $denormalizer);
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
     * @param ExchangeDenormalizer $denormalizer
     * @param array $sources
     * @param array $item
     * @return bool
     */
    private function findSourceToMapper($denormalizer, array $sources, array $item)
    {
        foreach ($sources as $source) {
            try {
                $this->addSourceToMapper($denormalizer, $source, $item);
                return true;
            } catch (\Exception $e) {
                // continue loop
            }
        }
        return false;
    }

    /**
     * Adds entitiy with assoc. items to mapper.
     *
     * @param ExchangeDenormalizer $denormalizer
     * @param object $object source
     * @param array  $data
     * @throws ImportException
     * @throws ORMException
     */
    private function addSourceToMapper($denormalizer, $object, array $data)
    {
        $this->em->refresh($object);
        if (!$this->em->contains($object)) {
             $this->em->merge($object);
        }
        $classMeta = $this->em->getClassMetadata(get_class($object));
        $denormalizer->addToMapper($object, $data, $classMeta);

        foreach ($classMeta->getAssociationMappings() as $assocItem) {
            $fieldName = $assocItem['fieldName'];
            $getMethod = $denormalizer->getReturnMethod($fieldName, $classMeta->getReflectionClass());
            if ($getMethod) {
                $subObject = $getMethod->invoke($object);
                $num = 0;
                if ($subObject instanceof PersistentCollection) {
                    if (is_a($assocItem['targetEntity'], "Mapbender\CoreBundle\Entity\Keyword", true)) {
                        continue;
                    } elseif (!isset($data[$fieldName])) {
                        throw new ImportException("Missing data for field {$fieldName} on target {$assocItem['targetEntity']}");
                    } elseif (count($data[$fieldName]) !== $subObject->count()) {
                        throw new ImportException("Collection member count mismatch for field {$fieldName}, " . count($data[$fieldName]) . " vs " . $subObject->count());
                    }
                    foreach ($subObject as $item) {
                        if (is_a($item, 'Mapbender\CoreBundle\Entity\SourceItem', true)) {
                            $subdata = $data[$fieldName][$num];
                            if ($classDef = $denormalizer->getClassDefinition($subdata)) {
                                $meta = $this->em->getClassMetadata($classDef[0]);
                                $criteria = $denormalizer->getIdentCriteria($subdata, $meta);
                                $od = null;
                                if ($denormalizer->isReference($subdata, $criteria)) {
                                    if (!$denormalizer->getAfterFromBefore($classDef[0], $criteria)) {
                                        $od = $denormalizer->getEntityData($classDef[0], $criteria);
                                        $this->addSourceToMapper($denormalizer, $item, $od);
                                    }
                                }
                                $num++;
                            } else {
                                throw new ImportException("Missing source item class definition");
                            }
                        }
                    }
                }
            }
        }
    }
}
