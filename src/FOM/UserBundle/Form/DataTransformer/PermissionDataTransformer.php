<?php

namespace FOM\UserBundle\Form\DataTransformer;

use FOM\UserBundle\Component\Ldap;
use FOM\UserBundle\Entity\Permission;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

/**
 * Service registered as form.ace.model_transformer
 * If you rewire fom.identities.provider to produce different types of users / groups for ACE assignments,
 * you should also rewire this service so it can process the new types properly.
 */
class PermissionDataTransformer implements DataTransformerInterface
{
    public function __construct()
    {
    }

    public function transform(mixed $value): array
    {
        /** @var ?Permission $value */
        return array(
            'permissions' => $value === null ? ["view"] : [$value->getPermission()],
        );
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
