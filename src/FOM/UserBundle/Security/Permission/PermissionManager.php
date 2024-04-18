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
        return $query->getQuery()->getResult();
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

}
