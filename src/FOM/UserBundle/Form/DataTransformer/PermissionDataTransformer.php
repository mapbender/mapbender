<?php

namespace FOM\UserBundle\Form\DataTransformer;

use FOM\UserBundle\Component\Ldap;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Security\Permission\AbstractResourceDomain;
use FOM\UserBundle\Security\Permission\PermissionManager;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class PermissionDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private AbstractResourceDomain $attributeDomain,
        private PermissionManager $permissionManager,
        private array $actions
    )
    {
    }

    public function transform(mixed $value): array
    {
        /** @var ?Permission[] $value */
        if (empty($value)) return [];
        $permissionEntity = $value[0];

        $subjectDomain = $this->permissionManager->findSubjectDomainFor($permissionEntity);
        $permissionList = array_map(fn(Permission $permission) => $permission->getAction(), $value);
        $permissionMap = array_map(fn(string $permission) => in_array($permission, $permissionList), $this->actions);
        return array(
            'permissions' => $permissionMap,
            'icon' => $subjectDomain->getIconClass(),
            'title' => $subjectDomain->getTitle($permissionEntity),
            'subjectJson' => $permissionEntity->getSubjectJson(),
        );
    }

    /**
     * Transforms an ACEType result into an ACE
     *
     * @param array $value
     * @return array{subjectJson: string, permissions: bool[]}
     */
    public function reverseTransform(mixed $value): array
    {
        unset($value["icon"]);
        unset($value["title"]);
        return $value;
    }

}
