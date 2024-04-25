<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Entity\User;

/**
 * Permission utility service; registered as 'fom.security.permission_manager'
 *
 * This manager is available as a service and can create/read/update/delete permissions.
 */
class PermissionManager
{
    public function __construct(
        /** @var AbstractAttributeDomain[] */
        private array                  $attributeDomains,
        /** @var AbstractSubjectDomain[] */
        private array                  $subjectDomains,
        private ?SubjectDomainPublic   $publicAccessDomain,
        private EntityManagerInterface $doctrineEM,
    )
    {
    }


    /**
     * @return Permission[]
     */
    public function findPermissions(
        AbstractAttributeDomain $attribute_domain,
        mixed                   $attribute,
        bool                    $group = true,
        bool                    $alwaysAddPublicAccess = false
    ): array
    {
        $repository = $this->doctrineEM->getRepository(Permission::class);
        $query = $repository->createQueryBuilder('p')->select('p');
        $attribute_domain->buildWhereClause($query, $attribute);
        /** @var Permission[] $permissionsUngrouped */
        $permissionsUngrouped = $query->getQuery()->getResult();

        if (!$group) return $permissionsUngrouped;

        $permissionsGrouped = [];
        foreach ($permissionsUngrouped as $permission) {
            $subjectJson = $permission->getSubjectJson();
            if (!isset($permissionsGrouped[$subjectJson])) $permissionsGrouped[$subjectJson] = [];
            $permissionsGrouped[$subjectJson][] = $permission;
        }

        // for e.g. applications the public access right should always be shown, whether a permission is set or not
        if ($alwaysAddPublicAccess && !$this->publicAccessDomain !== null
            && !array_key_exists($this->publicAccessDomain->getSubjectJson(), $permissionsGrouped)) {
            // add a dummy permission that only has the subject domain set
            $tempPermPublicAccess = new Permission();
            $tempPermPublicAccess->setSubjectDomain($this->publicAccessDomain->getSlug());
            $permissionsGrouped = [$this->publicAccessDomain->getSubjectJson() => [$tempPermPublicAccess]] + $permissionsGrouped;
        }

        return $permissionsGrouped;
    }

    public function findAttributeDomainFor(mixed $attribute): AbstractAttributeDomain
    {
        foreach ($this->attributeDomains as $attributeDomain) {
            if ($attributeDomain->supports($attribute)) return $attributeDomain;
        }
        throw new \InvalidArgumentException("No attribute domain registered that can handle attribute '$attribute' (type " . $attribute::class . ")");
    }

    public function findSubjectDomainFor(Permission $permission): AbstractSubjectDomain
    {
        foreach ($this->subjectDomains as $subjectDomain) {
            if ($subjectDomain->getSlug() === $permission->getSubjectDomain()) return $subjectDomain;
        }
        throw new \InvalidArgumentException("No subject domain registered for '{$permission->getSubjectDomain()}'");
    }

    /**
     * @return AssignableSubject[]
     */
    public function getAssignableSubjects(): array
    {
        $subjects = [];
        foreach ($this->subjectDomains as $subjectDomain) {
            array_push($subjects, ...$subjectDomain->getAssignableSubjects());
        }
        return $subjects;
    }

    /**
     * @param mixed $subject
     * @param array{subjectJson: string, permissions: bool[]} $permissionData
     * @return void
     */
    public function savePermissions(mixed $subject, array $permissionData): void
    {
        $attributeDomain = $this->findAttributeDomainFor($subject);
        $availablePermissions = $attributeDomain->getPermissions();

        // TODO: smarter method than deleting old permissions and adding new
        $oldPermissions = $this->findPermissions($attributeDomain, $subject, false);
        $oldPermissionIds = array_map(fn($p) => $p->getId(), $oldPermissions);
        $this->doctrineEM->getRepository(Permission::class)->createQueryBuilder('p')
            ->delete()->where('p.id IN (:ids)')->setParameter('ids', $oldPermissionIds)
            ->getQuery()->execute()
        ;

        foreach ($permissionData as $newPermission) {
            $json = json_decode($newPermission['subjectJson'], true);
            for ($i = 0; $i < min(count($newPermission['permissions']), count($availablePermissions)); $i++) {
                if ($newPermission['permissions'][$i] === true) {
                    $permissionEntity = new Permission();
                    $permissionEntity->setPermission($availablePermissions[$i]);
                    $permissionEntity->setGroup($json["group_id"] ? $this->doctrineEM->getReference(Group::class, $json["group_id"]) : null);
                    $permissionEntity->setUser($json["user_id"] ? $this->doctrineEM->getReference(User::class, $json["user_id"]) : null);
                    $permissionEntity->setSubject($json["subject"]);
                    $permissionEntity->setSubjectDomain($json["domain"]);
                    $attributeDomain->populatePermission($permissionEntity, $subject);
                    $this->doctrineEM->persist($permissionEntity);
                }
            }
        }
        $this->doctrineEM->flush();
    }

    public function grant(mixed $attribute, mixed $subject, string $permissionName, bool $isGranted)
    {
        // TODO: create this
    }

}
