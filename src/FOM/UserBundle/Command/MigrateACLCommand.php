<?php

namespace FOM\UserBundle\Command;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Security\Permission\ResourceDomainApplication;
use FOM\UserBundle\Security\Permission\ResourceDomainElement;
use FOM\UserBundle\Security\Permission\ResourceDomainInstallation;
use FOM\UserBundle\Security\Permission\SubjectDomainGroup;
use FOM\UserBundle\Security\Permission\SubjectDomainPublic;
use FOM\UserBundle\Security\Permission\SubjectDomainRegistered;
use FOM\UserBundle\Security\Permission\SubjectDomainUser;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Entity\Source;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Mapbender 4 introduced a new, simplified security system replacing Symfony's deprecated ACL bundle.
 * This command migrates most existing permissions
 */
#[AsCommand(self::COMMAND)]
class MigrateACLCommand extends Command
{
    const COMMAND = 'mapbender:security:migrate-from-acl';
    private EntityRepository $userRepo;
    private EntityRepository $groupRepo;
    private array $allApplicationIds;
    private array $allElementIds;

    public const MASK_VIEW = 1;           // 1 << 0
    public const MASK_CREATE = 2;         // 1 << 1
    public const MASK_EDIT = 4;           // 1 << 2
    public const MASK_DELETE = 8;         // 1 << 3
    public const MASK_UNDELETE = 16;      // 1 << 4
    public const MASK_OPERATOR = 32;      // 1 << 5
    public const MASK_MASTER = 64;        // 1 << 6
    public const MASK_OWNER = 128;        // 1 << 7

    public function __construct(private EntityManagerInterface $em)
    {
        parent::__construct(self::COMMAND);
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Migrates from Symfony ACL bundle to new mapbender security')
            ->setHelp(<<<EOT
The symfony/acl-bundle is deprecated since Symfony 4.0 and since Mapbender 4 is replaced by a voter-based security system.
This commands converts existing permissions to the new system
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ensureTableExists();

        $connection = $this->em->getConnection();
        $this->userRepo = $this->em->getRepository(User::class);
        $this->groupRepo = $this->em->getRepository(Group::class);

        $this->allApplicationIds = $this->em->getRepository(Application::class)
            ->createQueryBuilder('a')->select('a.id')->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN)
        ;
        $this->allElementIds = $this->em->getRepository(Element::class)
            ->createQueryBuilder('e')->select('e.id')->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN)
        ;

        $entries = $connection->executeQuery("
SELECT
    e.id,
    c.class_type,
    o.object_identifier,
    s.identifier,
    e.ace_order,
    e.mask
FROM acl_entries e
LEFT JOIN acl_classes c ON c.id = e.class_id
LEFT JOIN acl_object_identities o ON o.id = e.object_identity_id
LEFT JOIN acl_security_identities s ON s.id = e.security_identity_id;
        ")->fetchAllAssociative();

        /** @noinspection SqlWithoutWhere */
        $connection->executeQuery("DELETE FROM fom_permission;");

        foreach ($entries as $entry) {
            $newEntry = new Permission();
            $newEntry = $this->populateSubject($newEntry, $entry);
            if ($newEntry === null) continue;
            $this->populateAttributeAndSave($newEntry, $entry);
        }

        $publishedApplications = $this->em->getConnection()->executeQuery('SELECT id FROM mb_core_application WHERE published IS TRUE')->fetchFirstColumn();
        foreach ($publishedApplications as $applicationId) {
            $newEntry = new Permission(
                subjectDomain: SubjectDomainPublic::SLUG,
                resourceDomain: ResourceDomainApplication::SLUG,
                application: $this->em->getReference(Application::class, $applicationId),
                action: ResourceDomainApplication::ACTION_VIEW
            );
            $this->em->persist($newEntry);
        }

        $this->em->flush();
        return 0;
    }

    private function populateAttributeAndSave(Permission $newEntry, array $entry): void
    {
        if ($entry["class_type"] === Group::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & self::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_GROUPS);
            }
            if (($mask & self::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_GROUPS);
            }
            if (($mask & self::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_GROUPS);
            }
            if (($mask & self::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_GROUPS);
            }
            return;
        }

        if ($entry["class_type"] === User::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & self::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_USERS);
            }
            if (($mask & self::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_USERS);
            }
            if (($mask & self::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_USERS);
            }
            if (($mask & self::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_USERS);
            }
            return;
        }

        if ($entry["class_type"] === Application::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & self::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_ALL_APPLICATIONS);
            }
            if (($mask & self::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS);
            }
            if (($mask & self::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_ALL_APPLICATIONS);
            }
            if (($mask & self::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_ALL_APPLICATIONS);
            }
            if (($mask & self::MASK_OPERATOR) > 0 || ($mask & self::MASK_MASTER) > 0 || ($mask & self::MASK_OWNER) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_OWN_ALL_APPLICATIONS);
            }
            return;
        }

        if ($entry["class_type"] === Application::class && $entry["object_identifier"] !== null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainApplication::SLUG);
            $applicationId = $entry["object_identifier"];
            if (!in_array($applicationId, $this->allApplicationIds)) {
                echo "WARNING: application id $applicationId not found for entry " . $entry["id"] . "\n";
                return;
            }
            $application = $this->em->getReference(Application::class, $applicationId);
            $newEntry->setApplication($application);
            if (($mask & self::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainApplication::ACTION_VIEW);
            }
            if (($mask & self::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainApplication::ACTION_EDIT);
            }
            if (($mask & self::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainApplication::ACTION_DELETE);
            }
            if (($mask & self::MASK_OPERATOR) > 0 || ($mask & self::MASK_MASTER) > 0 || ($mask & self::MASK_OWNER) > 0) {
                $this->saveEntry($newEntry, ResourceDomainApplication::ACTION_MANAGE_PERMISSIONS);
            }
            return;
        }

        if ($entry["class_type"] === Element::class && $entry["object_identifier"] !== null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainElement::SLUG);
            $elementId = $entry["object_identifier"];
            if (!in_array($elementId, $this->allElementIds)) {
                echo "WARNING: element id $elementId not found for entry " . $entry["id"] . "\n";
                return;
            }
            $element = $this->em->getReference(Element::class, $elementId);
            $newEntry->setElement($element);
            if (($mask & self::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainElement::ACTION_VIEW);
            }
            return;
        }

        if ($entry["class_type"] === Source::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & self::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_SOURCES);
            }
            if (($mask & self::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_SOURCES);
            }
            if (($mask & self::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_REFRESH_SOURCES);
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES);
            }
            if (($mask & self::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_SOURCES);
            }
            return;
        }

        if ($entry["class_type"] === "Symfony\Component\Security\Acl\Domain\Acl" && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & self::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_MANAGE_PERMISSION);
            }
            return;
        }

        echo "WARNING: Invalid class type " . $entry["class_type"] . "(object identifier " . $entry["object_identifier"] . ") for entry id " . $entry["id"] . "\n";
    }

    private function populateSubject(Permission $newEntry, array $entry): ?Permission
    {
        if ($entry["identifier"] === 'IS_AUTHENTICATED_ANONYMOUSLY') {
            $newEntry->setSubjectDomain(SubjectDomainPublic::SLUG);
            return $newEntry;
        }

        if ($entry["identifier"] === 'ROLE_USER') {
            $newEntry->setSubjectDomain(SubjectDomainRegistered::SLUG);
            return $newEntry;
        }

        if (str_starts_with($entry["identifier"], 'FOM\UserBundle\Entity\User-')) {
            $username = substr($entry["identifier"], 27);
            $user = $this->userRepo->findOneBy(["username" => $username]);
            if (!$user) {
                $user = $this->userRepo->findOneBy(["email" => $username]);
            }
            if (!$user) {
                echo "WARNING: could not find user $username, defined in entry id " . $entry["id"] . "\n";
                return $newEntry;
            }
            $newEntry->setSubjectDomain(SubjectDomainUser::SLUG);
            $newEntry->setUser($user);
            return $newEntry;
        }

        if (str_starts_with($entry["identifier"], 'ROLE_GROUP_')) {
            $groupName = substr($entry["identifier"], 11);
            $group = $this->groupRepo->createQueryBuilder('g')
                ->andWhere("lower(g.title) = lower(:groupName)")
                ->setParameter('groupName', $groupName)
                ->getQuery()
                ->getResult()
            ;
            if (is_array($group)) $group = count($group) > 0 ? $group[0] : null;
            if (!$group) {
                echo "WARNING: could not find group $groupName, defined in entry id " . $entry["id"] . "\n";
                return $newEntry;
            }
            $newEntry->setSubjectDomain(SubjectDomainGroup::SLUG);
            $newEntry->setGroup($group);
            return $newEntry;
        }

        echo "WARNING: Invalid identifier " . $entry["identifier"] . " for entry id " . $entry["id"] . "\n";
        return null;
    }

    private function saveEntry(Permission $newEntry, string $permission)
    {
        // ignore rights for superuser, they can do everything anyway
        if ($newEntry->getSubjectDomain() === SubjectDomainUser::SLUG && $newEntry->getUser()?->getId() === 1) return;
        $entry = clone $newEntry;
        $entry->setAction($permission);
        $this->em->persist($entry);
    }

    private function ensureTableExists()
    {
        try {
            $check = $this->em->getRepository(Permission::class)->createQueryBuilder('p')->select('p.id')->setMaxResults(1)->getQuery()->getResult();
            return;
        } catch (TableNotFoundException $e) {
            $schemaTool = new SchemaTool($this->em);
            $classMetadata = $this->em->getClassMetadata(Permission::class);
            $sqls = $schemaTool->getCreateSchemaSql([$classMetadata]);
            foreach ($sqls as $sql) {
                if (!str_contains($sql, 'acl')) $this->em->getConnection()->executeQuery($sql);
            }
        }
    }
}
