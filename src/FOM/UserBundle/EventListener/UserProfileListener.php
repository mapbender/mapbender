<?php

namespace FOM\UserBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Id\AssignedGenerator;
use Doctrine\DBAL\Platforms\OraclePlatform;
use Doctrine\ORM\Mapping\ClassMetadata;
use FOM\UserBundle\Entity\User;

/**
 * Event listener for adding user profile on the fly
 *
 * @author Christian Wygoda
 */
#[AsDoctrineListener(event: Events::loadClassMetadata, priority: 500, connection: 'default')]
class UserProfileListener
{
    protected string $defaultUserEntityName = User::class;
    protected array $patchProgress = array();
    const PATCH_STARTED = 1;
    const PATCH_PERFORMED = 2;

    public function __construct(protected string $profileEntityName)
    {
        $this->defaultUserEntityName = 'FOM\UserBundle\Entity\User';
    }

    public function loadClassMetadata(LoadClassMetadataEventArgs $args): void
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

    protected function patchUserEntity(ClassMetadata $metadata): void
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

    protected function patchProfileEntity(ClassMetadata $metadata, AbstractPlatform $platform): void
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

    protected function patchProfilePk(ClassMetadata $metadata): void
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

    protected function isUserEntity(string $className): bool
    {
        // ONLY detect the default class
        return ltrim($className, '\\') === $this->defaultUserEntityName;
    }

    protected function isProfileEntity(string $className): bool
    {
        // ONLY detect the configured class
        // Relation from one User entity class to multiple different profile entity classes
        // cannot be established, so we won't try
        return ltrim($className, '\\') === $this->profileEntityName;
    }
}
