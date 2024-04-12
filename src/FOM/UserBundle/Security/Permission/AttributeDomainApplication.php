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

    public function buildWhereClause(string $permission, mixed $subject): ?WhereClauseComponent
    {
        /** @var Application $subject */
        return new WhereClauseComponent(
            whereClause: "p.permission = :permission AND p.attribute_domain = '".self::SLUG."' AND p.application_id : :application_id",
            variables: ['permission' => $permission, 'application_id' => $subject->getId()]
        );
    }
}
