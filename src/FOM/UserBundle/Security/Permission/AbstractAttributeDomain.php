<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;

abstract class AbstractAttributeDomain
{
    const CSS_CLASS_SUCCESS = "success";
    const CSS_CLASS_WARNING = "warning";
    const CSS_CLASS_DANGER = "danger";
    /**
     * returns the unique slug for this attribute domain that will be saved
     * in the database's "attribute_domain" column within the fom_permission table.
     * Convention: also declare a const string SLUG for easy access
     * @return string
     */
    abstract function getSlug(): string;

    /**
     * advertises all permissions that are valid within this attribute domain. In the edit screen, permissions
     * will be displayed in the order returned here. If [self::isHierarchical()] is true, return the weakest
     * permission first and the strongest kast
     * @return string[]
     */
    abstract function getPermissions(): array;

    /**
     * get the prefix for translation strings for the permissions themselves.
     * e.g. if the prefix is fom.security, the permission "view" will be translated with "fom.security.view"
     * a help string is additionally suffixed with "_help", e.g. "fom.security.view_help"
     */
    abstract function getTranslationPrefix(): string;

    /**
     * determines whether the permissions available in this attribute domain are hierarchical.
     * hierarchical permissions imply, that a weaker permission is automatically granted if a stronger one is
     * (e.g. a user that can edit entries can also view them). Hierarchical permissions result in fewer
     * database entries
     * if permissions are not hierarchical, all permissions are regared as independent.
     * @return bool
     */
    function isHierarchical(): bool
    {
        return false;
    }

    /**
     * determines if the attribute domain supports a given subject and (optionally) a given permission
     * @param mixed|null $subject
     * @param string|null $permission
     * @return bool
     */
    abstract function supports(mixed $subject, ?string $permission = null): bool;

    /**
     * checks if a permission entry (attribute-related subset of fom_permission entity) applies to the fiven permission name and subject
     * this is used for isGranted checks, where all permissions for a user are cached to minimize calls to the database
     * @param array{permission: string, attribute_domain: string, attribute: ?string, element_id: ?int, application_id: ?int} $permission
     * @param string $permissionName
     * @param mixed $subject
     * @return bool
     */
    public function matchesPermission(array $permission, string $permissionName, mixed $subject): bool
    {
        return $permission["attribute_domain"] === $this->getSlug() && (
            $this->isHierarchical()
                ? in_array($permissionName, $this->inheritedPermissions($permission["permission"]))
                : $permissionName === $permission["permission"]
            );
    }

    /**
     * Build an SQL where clause that matches the supplied subject
     * The permission table is aliased as `p`
     * The permission subject is available as argument, if you need information from another service, use dependency injection.
     */
    abstract public function buildWhereClause(QueryBuilder $q, mixed $subject): void;

    /**
     * @param string $permission
     * @return string[]
     */
    public function inheritedPermissions(string $permission): array
    {
        if (!$this->isHierarchical()) return [$permission];
        $hierarchy = [];
        foreach ($this->getPermissions() as $perm) {
            $hierarchy[] = $perm;
            if ($perm === $permission) return $hierarchy;
        }
        return [];
    }

    public function getCssClassForPermission(string $permission): string
    {
        return self::CSS_CLASS_SUCCESS;
    }

    public function populatePermission(Permission $permission, mixed $subject): void
    {
        $permission->setAttributeDomain($this->getSlug());
    }

}
