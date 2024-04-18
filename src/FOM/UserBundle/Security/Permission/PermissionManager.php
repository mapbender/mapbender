<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\Permission;

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
        private EntityManagerInterface $doctrine,
    )
    {
    }


    /**
     * @return Permission[]
     */
    public function findPermissions(AbstractAttributeDomain $attribute_domain, mixed $attribute): array
    {
        $repository = $this->doctrine->getRepository(Permission::class);
        $query = $repository->createQueryBuilder('p')->select('p');
        $attribute_domain->buildWhereClause($query, $attribute);
        /** @var Permission[] $permissionsUngrouped */
        $permissionsUngrouped =  $query->getQuery()->getResult();

        $permissionsGrouped = [];
        foreach ($permissionsUngrouped as $permission) {
            $subjectJson = $permission->getSubjectJson();
            if (!isset($permissionsGrouped[$subjectJson])) $permissionsGrouped[$subjectJson] = [];
            $permissionsGrouped[$subjectJson][] = $permission;
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

}
