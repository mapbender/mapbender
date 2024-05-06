<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Represents a domain of resources where custom permissions can be granted on.
 * This can be an application, an element or the whole mapbender installation.
 *
 * Resource domains need to be tagged as `fom.security.resource_domain`
 */
abstract class AbstractResourceDomain
{
    const CSS_CLASS_SUCCESS = "success";
    const CSS_CLASS_WARNING = "warning";
    const CSS_CLASS_DANGER = "danger";

    /**
     * returns the unique slug for this resource domain that will be saved
     * in the database's "resource_domain" column within the fom_permission table.
     * Convention: also declare a const string SLUG for easy access
     * @return string
     */
    abstract function getSlug(): string;

    /**
     * advertises all actions that are valid within this resource domain. In the edit screen, actions
     * will be displayed in the order returned here. If [self::isHierarchical()] is true, return the weakest
     * action first and the strongest last
     * @return string[]
     */
    abstract function getActions(): array;

    /**
     * get the prefix for the translation strings for this resource's actions.
     * e.g. if the prefix is fom.security, the action "view" will be translated with "fom.security.view"
     * a help string is additionally suffixed with "_help", e.g. "fom.security.view_help"
     */
    abstract function getTranslationPrefix(): string;

    /**
     * determines whether the actions available in this resource domain are hierarchical.
     * hierarchical actions imply that a weaker action is automatically granted if a stronger one is
     * (e.g. a user that can edit entries can also view them).
     * Hierarchical actions result in fewer database entries.
     * if actions are not hierarchical, all actions are regarded as independent.
     */
    function isHierarchical(): bool
    {
        return false;
    }

    /**
     * determines if the resource domain supports a given resource and (optionally) a given action
     */
    abstract function supports(mixed $resource, ?string $action = null): bool;

    /**
     * checks if a permission entity applies to the given action and subject
     * the logic is similar to `self::buildWhereClause`, however, this method is used for
     * isGranted checks, where permissions for a user are cached to avoid duplicated calls to the database
     */
    public function matchesPermission(Permission $permission, string $action, mixed $resource): bool
    {
        return $permission->getResourceDomain() === $this->getSlug() && (
            $this->isHierarchical()
                ? in_array($action, $this->inheritedActions($permission->getAction()))
                : $action === $permission->getAction()
            );
    }

    /**
     * Modify the QueryBuilder using an "orWhere" statement to match the supplied resource
     * The permission table is aliased as `p`
     * The resource is available as argument, if you need information from another service, use dependency injection.
     */
    public function buildWhereClause(QueryBuilder $q, mixed $resource): void
    {
        $q->orWhere("p.resourceDomain = '" . $this->getSlug() . "'");
    }

    /**
     * Returns a list of all actions that are implicitly granted if the given action is granted
     * If `isHierarchical()` is false, only the action itself is returned, otherwise all actions that
     * are defined before the given action in `getActions()` will be returned
     * @return string[]
     */
    public function inheritedActions(string $action): array
    {
        if (!$this->isHierarchical()) return [$action];
        $hierarchy = [];
        foreach ($this->getActions() as $perm) {
            $hierarchy[] = $perm;
            if ($perm === $action) return $hierarchy;
        }
        return [];
    }

    /**
     * Returns the css class that will be added to a permission entry in the permission table for the given action
     * @return self::CSS_CLASS_SUCCESS|self::CSS_CLASS_WARNING|self::CSS_CLASS_DANGER
     */
    public function getCssClassForAction(string $action): string
    {
        return self::CSS_CLASS_SUCCESS;
    }

    /**
     * Writes all data required to identify the given resource to the given permission entity
     */
    public function populatePermission(Permission $permission, mixed $resource): void
    {
        $permission->setResourceDomain($this->getSlug());
    }

    /**
     * Override this method if you want to modify the regular behaviour (default deny except a permission is defined for a resource)
     * @return null|bool null if the defined permissions should decide, true to override the access decision to "granted", false to override to "denied"
     */
    public function overrideDecision(mixed $resource, string $action, ?UserInterface $user, PermissionManager $manager): bool|null
    {
        return null;
    }

}
