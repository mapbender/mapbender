<?php

namespace FOM\UserBundle\Security\Permission;

use Mapbender\CoreBundle\Entity\Application;

class AttributeDomainApplication extends AbstractAttributeDomain
{
    const SLUG = "application";

    const PERMISSION_VIEW = "view";
    const PERMISSION_EDIT = "edit";
    const PERMISSION_DELETE = "delete";
    const PERMISSION_MANAGE_PERMISSIONS = "manage_permissions";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function getPermissions(): array
    {
        return [
            self::PERMISSION_VIEW,
            self::PERMISSION_EDIT,
            self::PERMISSION_DELETE,
            self::PERMISSION_MANAGE_PERMISSIONS
        ];
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return $subject instanceof Application && in_array($permission, $this->getPermissions());
    }

    public function isHierarchical(): bool
    {
        return true;
    }

    public function matchesPermission(array $permission, string $permissionName, mixed $subject): bool
    {
        /** @var Application $subject */

        return parent::matchesPermission($permission, $permissionName, $subject)
            && $permission["application_id"] === $subject->getId();
    }
}
