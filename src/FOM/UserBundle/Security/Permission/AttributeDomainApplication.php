<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
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

    public function supports(mixed $subject, ?string $permission = null): bool
    {
        return $subject instanceof Application
            && ($permission === null || in_array($permission, $this->getPermissions()));
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

    public function buildWhereClause(QueryBuilder $q, mixed $subject): void
    {
        /** @var Application $subject */
        $q->orWhere("(p.application = :application AND p.attributeDomain = '" . self::SLUG . "')")
            ->setParameter('application', $subject)
        ;
    }

    public function getCssClassForPermission(string $permission): string
    {
        return match ($permission) {
            self::PERMISSION_VIEW => self::CSS_CLASS_SUCCESS,
            self::PERMISSION_EDIT => self::CSS_CLASS_WARNING,
            default => self::CSS_CLASS_DANGER,
        };
    }

    public function populatePermission(Permission $permission, mixed $subject): void
    {
        /** @var Application $subject */
        parent::populatePermission($permission, $subject);
        $permission->setApplication($subject);
    }
}
