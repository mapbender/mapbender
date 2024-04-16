<?php

namespace FOM\UserBundle\Security\Permission;

abstract class AbstractAttributeDomain
{
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
     * determines if the attribute domain supports a given permission on a subject
     * @param string $permission
     * @param mixed|null $subject
     * @return bool
     */
    abstract function supports(string $permission, mixed $subject): bool;

    /**
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

}
