<?php

namespace FOM\UserBundle\Form\DataTransformer;

use FOM\UserBundle\Component\Ldap;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Security\Permission\AbstractAttributeDomain;
use FOM\UserBundle\Security\Permission\PermissionManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class PermissionDataTransformer implements DataTransformerInterface
{
    public function __construct(private AbstractAttributeDomain $attributeDomain, private PermissionManager $permissionManager)
    {
    }

    public function transform(mixed $value): array
    {
        /** @var ?Permission $value */
        if ($value === null) return [];

        $allPermissions = $this->attributeDomain->getPermissions();
        $subjectDomain = $this->permissionManager->findSubjectDomainFor($value);
        $permissionMap = array_map(fn(string $permission) => $permission === $value->getPermission(), $allPermissions);
        $a = array(
            'permissions' => $permissionMap,
            'icon' => $subjectDomain->getIconClass(),
            'title' => $subjectDomain->getTitle($value),
        );
        return $a;
    }

    /**
     * Transforms an ACEType result into an ACE
     *
     * @param object $data
     * @return array
     */
    public function reverseTransform(mixed $value): Permission
    {
        /** @var array $value */
        return new Permission();
    }

}
