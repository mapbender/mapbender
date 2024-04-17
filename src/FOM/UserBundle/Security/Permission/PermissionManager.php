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


    public function findPermissions(AbstractAttributeDomain $attribute_domain, mixed $attribute): array
    {
        $repository = $this->doctrine->getRepository(Permission::class);
        return $repository->findAll();
    }

    public function findAttributeDomainFor(mixed $attribute): AbstractAttributeDomain
    {
        // TODO
        return $this->attributeDomains[0];
    }

}
