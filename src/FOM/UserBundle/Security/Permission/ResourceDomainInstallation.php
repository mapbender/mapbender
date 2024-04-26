<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;

class ResourceDomainInstallation extends AbstractResourceDomain
{
    const SLUG = "installation";

    const ACTION_CREATE_APPLICATIONS = "create_applications";
    const ACTION_VIEW_ALL_APPLICATIONS = "view_all_applications";
    const ACTION_EDIT_ALL_APPLICATIONS = "edit_all_applications";
    const ACTION_DELETE_ALL_APPLICATIONS = "delete_all_applications";
    const ACTION_OWN_ALL_APPLICATIONS = "own_all_applications";

    const ACTION_VIEW_SOURCES = "view_sources";
    const ACTION_CREATE_SOURCES = "create_sources";
    const ACTION_REFRESH_SOURCES = "refresh_sources";
    const ACTION_EDIT_FREE_INSTANCES = "edit_free_instances";
    const ACTION_DELETE_SOURCES = "delete_sources";

    const ACTION_MANAGE_PERMISSION = "manage_permissions";

    const ACTION_VIEW_USERS = "view_users";
    const ACTION_CREATE_USERS = "create_users";
    const ACTION_EDIT_USERS = "edit_users";
    const ACTION_DELETE_USERS = "delete_users";

    const ACTION_VIEW_GROUPS = "view_groups";
    const ACTION_CREATE_GROUPS = "create_groups";
    const ACTION_EDIT_GROUPS = "edit_groups";
    const ACTION_DELETE_GROUPS = "delete_groups";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function supports(mixed $resource, ?string $action = null): bool
    {
        return $resource === null &&
            ($action === null || in_array($action, $this->getActions()));
    }

    public function getActions(): array
    {
        return [
            self::ACTION_CREATE_APPLICATIONS,
            self::ACTION_VIEW_ALL_APPLICATIONS,
            self::ACTION_EDIT_ALL_APPLICATIONS,
            self::ACTION_DELETE_ALL_APPLICATIONS,
            self::ACTION_OWN_ALL_APPLICATIONS,
            self::ACTION_VIEW_SOURCES,
            self::ACTION_CREATE_SOURCES,
            self::ACTION_REFRESH_SOURCES,
            self::ACTION_EDIT_FREE_INSTANCES,
            self::ACTION_DELETE_SOURCES,
            self::ACTION_MANAGE_PERMISSION,
            self::ACTION_VIEW_USERS,
            self::ACTION_CREATE_USERS,
            self::ACTION_EDIT_USERS,
            self::ACTION_DELETE_USERS,
            self::ACTION_VIEW_GROUPS,
            self::ACTION_CREATE_GROUPS,
            self::ACTION_EDIT_GROUPS,
            self::ACTION_DELETE_GROUPS,
        ];
    }

    public function buildWhereClause(QueryBuilder $q, mixed $resource): void
    {
        $q->orWhere("p.resourceDomain = '" . self::SLUG . "'");
    }

    public function getCssClassForAction(string $action): string
    {
        // TODO
        return parent::getCssClassForAction($action);
    }

    function getTranslationPrefix(): string
    {
        return "fom.security.resource.installation";
    }
}
