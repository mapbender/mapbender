<?php

namespace FOM\UserBundle\Security\Permission;

class AttributeDomainInstallation extends AbstractAttributeDomain
{
    const SLUG = "installation";

    const PERMISSION_CREATE_APPLICATIONS = "create_applications";
    const PERMISSION_VIEW_ALL_APPLICATIONS = "view_all_applications";
    const PERMISSION_EDIT_ALL_APPLICATIONS = "edit_all_applications";
    const PERMISSION_DELETE_ALL_APPLICATIONS = "delete_all_applications";
    const PERMISSION_OWN_ALL_APPLICATIONS = "own_all_applications";

    const PERMISSION_VIEW_SOURCES = "view_sources";
    const PERMISSION_CREATE_SOURCES = "create_sources";
    const PERMISSION_REFRESH_SOURCES = "refresh_sources";
    const PERMISSION_EDIT_FREE_INSTANCES = "edit_free_instances";
    const PERMISSION_DELETE_SOURCES = "delete_sources";

    const PERMISSION_MANAGE_PERMISSION = "manage_permissions";

    const PERMISSION_VIEW_USERS = "view_users";
    const PERMISSION_CREATE_USERS = "create_users";
    const PERMISSION_EDIT_USERS = "edit_users";
    const PERMISSION_DELETE_USERS = "delete_users";

    const PERMISSION_VIEW_GROUPS = "view_groups";
    const PERMISSION_CREATE_GROUPS = "create_groups";
    const PERMISSION_EDIT_GROUPS = "edit_groups";
    const PERMISSION_DELETE_GROUPS = "delete_groups";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return $subject === null && in_array($permission, $this->getPermissions());
    }

    public function getPermissions(): array
    {
        return [
            self::PERMISSION_CREATE_APPLICATIONS,
            self::PERMISSION_VIEW_ALL_APPLICATIONS,
            self::PERMISSION_EDIT_ALL_APPLICATIONS,
            self::PERMISSION_DELETE_ALL_APPLICATIONS,
            self::PERMISSION_OWN_ALL_APPLICATIONS,
            self::PERMISSION_VIEW_SOURCES,
            self::PERMISSION_CREATE_SOURCES,
            self::PERMISSION_REFRESH_SOURCES,
            self::PERMISSION_EDIT_FREE_INSTANCES,
            self::PERMISSION_DELETE_SOURCES,
            self::PERMISSION_MANAGE_PERMISSION,
            self::PERMISSION_VIEW_USERS,
            self::PERMISSION_CREATE_USERS,
            self::PERMISSION_EDIT_USERS,
            self::PERMISSION_DELETE_USERS,
            self::PERMISSION_VIEW_GROUPS,
            self::PERMISSION_CREATE_GROUPS,
            self::PERMISSION_EDIT_GROUPS,
            self::PERMISSION_DELETE_GROUPS,
        ];
    }

}
