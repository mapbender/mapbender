<?php

namespace FOM\UserBundle\Command;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
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
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * Mapbender 4 introduced a new, simplified security system replacing Symfony's deprecated ACL bundle.
 * This command migrates most existing permissions
 */
class MigrateACLCommand extends Command
{
    const COMMAND = 'mapbender:security:migrate-from-acl';
    private EntityRepository $userRepo;
    private EntityRepository $groupRepo;
    private array $allApplicationIds;
    private array $allElementIds;

    public function __construct(private EntityManagerInterface $doctrine)
    {
        parent::__construct(self::COMMAND);
    }

    protected function configure()
    {
        $this
            ->setDescription('Migrates from Symfony ACL bundle to new mapbender security')
            ->setHelp(<<<EOT
The symfony/acl-bundle is deprecated since Symfony 4.0 and since Mapbender 4 is replaced by a voter-based security system.
This commands converts existing permissions to the new system
EOT
            )
            ->setName(self::COMMAND)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $connection = $this->doctrine->getConnection();
        $this->userRepo = $this->doctrine->getRepository(User::class);
        $this->groupRepo = $this->doctrine->getRepository(Group::class);

        $this->allApplicationIds = $this->doctrine->getRepository(Application::class)
            ->createQueryBuilder('a')->select('a.id')->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR_COLUMN)
        ;
        $this->allElementIds = $this->doctrine->getRepository(Element::class)
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
        $this->doctrine->flush();
        return 0;
    }

    private function populateAttributeAndSave(Permission $newEntry, array $entry): void
    {
        if ($entry["class_type"] === Group::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & MaskBuilder::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_GROUPS);
            }
            if (($mask & MaskBuilder::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_GROUPS);
            }
            if (($mask & MaskBuilder::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_GROUPS);
            }
            if (($mask & MaskBuilder::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_GROUPS);
            }
            return;
        }

        if ($entry["class_type"] === User::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & MaskBuilder::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_USERS);
            }
            if (($mask & MaskBuilder::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_USERS);
            }
            if (($mask & MaskBuilder::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_USERS);
            }
            if (($mask & MaskBuilder::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_USERS);
            }
            return;
        }

        if ($entry["class_type"] === Application::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & MaskBuilder::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_ALL_APPLICATIONS);
            }
            if (($mask & MaskBuilder::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS);
            }
            if (($mask & MaskBuilder::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_ALL_APPLICATIONS);
            }
            if (($mask & MaskBuilder::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_ALL_APPLICATIONS);
            }
            if (($mask & MaskBuilder::MASK_OPERATOR) > 0 || ($mask & MaskBuilder::MASK_MASTER) > 0 || ($mask & MaskBuilder::MASK_OWNER) > 0) {
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
            $application = $this->doctrine->getReference(Application::class, $applicationId);
            $newEntry->setApplication($application);
            if (($mask & MaskBuilder::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainApplication::ACTION_VIEW);
            }
            if (($mask & MaskBuilder::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainApplication::ACTION_EDIT);
            }
            if (($mask & MaskBuilder::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainApplication::ACTION_DELETE);
            }
            if (($mask & MaskBuilder::MASK_OPERATOR) > 0 || ($mask & MaskBuilder::MASK_MASTER) > 0 || ($mask & MaskBuilder::MASK_OWNER) > 0) {
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
            $element = $this->doctrine->getReference(Element::class, $elementId);
            $newEntry->setElement($element);
            if (($mask & MaskBuilder::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainElement::ACTION_VIEW);
            }
            return;
        }

        if ($entry["class_type"] === Source::class && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & MaskBuilder::MASK_VIEW) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_VIEW_SOURCES);
            }
            if (($mask & MaskBuilder::MASK_CREATE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_CREATE_SOURCES);
            }
            if (($mask & MaskBuilder::MASK_EDIT) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_REFRESH_SOURCES);
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_EDIT_FREE_INSTANCES);
            }
            if (($mask & MaskBuilder::MASK_DELETE) > 0) {
                $this->saveEntry($newEntry, ResourceDomainInstallation::ACTION_DELETE_SOURCES);
            }
            return;
        }

        if ($entry["class_type"] === "Symfony\Component\Security\Acl\Domain\Acl" && $entry["object_identifier"] === null) {
            $mask = $entry["mask"];
            $newEntry->setResourceDomain(ResourceDomainInstallation::SLUG);
            if (($mask & MaskBuilder::MASK_EDIT) > 0) {
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
        $this->doctrine->persist($entry);
    }
}
