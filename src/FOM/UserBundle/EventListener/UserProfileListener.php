<?php

namespace FOM\UserBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Event listener for adding user profile on the fly
 *
 * @author Christian Wygoda
 */
class UserProfileListener implements EventSubscriber
{
    /** @var string */
    protected $profileEntityName;
    /** @var string */
    protected $defaultUserEntityName;

    protected $patchProgress = array();
    const PATCH_STARTED = 1;
    const PATCH_PERFORMED = 2;

    /**
     * @param string $profileEntityClass
     */
    public function __construct($profileEntityClass)
    {
        $this->profileEntityName = $profileEntityClass;
        $this->defaultUserEntityName = 'FOM\UserBundle\Entity\User';
    }

    public function getSubscribedEvents()
    {
        return array(
            'loadClassMetadata',
        );
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args)
    {
        $metadata = $args->getClassMetadata();
        $metadataClass = $metadata->getName();
        if (empty($this->patchProgress[$metadataClass]) || $this->patchProgress[$metadataClass] !== self::PATCH_PERFORMED) {
            if (!$this->profileEntityName) {
                if (\is_a($metadataClass, 'FOM\\UserBundle\\Entity\\AbstractProfile', true)) {
                   // Profile is not active but may need an ID mapping for schema validity
                    $this->patchProfilePk($metadata);
                }
                $this->patchProgress[$metadataClass] = self::PATCH_PERFORMED;
            } elseif ($this->isUserEntity($metadataClass)) {
                $this->patchProgress[$metadataClass] = self::PATCH_STARTED;
                if (!$metadata->hasAssociation('profile')) {
                    // trigger patching of Profile entity first
                    $em = $args->getEntityManager();
                    /** @noinspection PhpUnusedLocalVariableInspection */
                    $unusedResult = $em->getClassMetadata($this->profileEntityName);
                }
                $this->patchUserEntity($metadata);
                $this->patchProgress[$metadataClass] = self::PATCH_PERFORMED;
            } elseif ($this->isProfileEntity($metadataClass)) {
                $this->patchProgress[$metadataClass] = self::PATCH_STARTED;
                $em = $args->getEntityManager();
                $platform = $em->getConnection()->getDatabasePlatform();
                $this->patchProfileEntity($metadata, $platform);
                $this->patchProgress[$metadataClass] = self::PATCH_PERFORMED;
            }
        }
    }

    protected function patchUserEntity(ClassMetadata $metadata)
    {
        if (!$metadata->hasAssociation('profile')) {
            $metadata->mapOneToOne(array(
                'fieldName' => 'profile',
                'targetEntity' => $this->profileEntityName,
                'mappedBy' => 'uid',
                'cascade' => array('persist'),
            ));
        }
    }

    protected function patchProfileEntity(ClassMetadata $metadata, AbstractPlatform $platform)
    {
        /** @see https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/basic-mapping.html#quoting-reserved-words */
        $uidColname = $platform->quoteIdentifier('uid');
        if ($platform instanceof OraclePlatform) {
            // why..?
            $uidColname = strtoupper($uidColname);
        }

        $metadata->setIdGenerator(new AssignedGenerator());
        if (!$metadata->getIdentifierFieldNames()) {
            $metadata->setIdentifier(array('uid'));
        }
        if (!$metadata->hasAssociation('uid')) {
            $metadata->mapOneToOne(array(
                'fieldName' => 'uid',
                'targetEntity' => $this->defaultUserEntityName,
                'inversedBy' => 'profile',
                'id' => true,
                'joinColumns' => array(
                    array(
                        'name' => $uidColname,
                        'referencedColumnName' => 'id',
                        'unique' => true,
                    ),
                ),
            ));
        }
    }

    protected function patchProfilePk(ClassMetadata $metadata)
    {
        $metadata->setIdGenerator(new AssignedGenerator());
        if (!$metadata->getIdentifierFieldNames()) {
            $metadata->setIdentifier(array('uid'));
        }
        if (!$metadata->hasField('uid')) {
            $metadata->mapField(array(
                'fieldName' => 'uid',
                'type' => 'integer',
            ));
        }
    }

    /**
     * @param string $className
     * @return boolean
     */
    protected function isUserEntity($className)
    {
        // ONLY detect the default class
        return ltrim($className, '\\') === $this->defaultUserEntityName;
    }

    /**
     * @param string $className
     * @return boolean
     */
    protected function isProfileEntity($className)
    {
        // ONLY detect the configured class
        // Relation from one User entity class to multiple different profile entity classes
        // cannot be established, so we won't try
        return ltrim($className, '\\') === $this->profileEntityName;
    }
}
