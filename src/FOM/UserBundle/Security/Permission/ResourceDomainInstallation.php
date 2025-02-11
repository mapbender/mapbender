<?php

namespace FOM\UserBundle\Security\Permission;

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

    const ACTION_ACCESS_API = "access_api";
    const ACTION_UPLOAD_FILES = "upload_files";

    public const CATEGORY_APPLICATION = "applications";
    public const CATEGORY_SOURCES = "sources";
    public const CATEGORY_PERMISSIONS = "permissions";
    public const CATEGORY_USERS = "users";
    public const CATEGORY_GROUPS = "groups";
    public const CATEGORY_API = "api";

    protected array $categoryList;
    protected array $permissionList;


    /**
     * @param GlobalPermissionProvider[] $globalPermissionProviders
     */
    public function __construct(array $globalPermissionProviders)
    {
        $this->categoryList = $this->defaultGroups();
        $this->permissionList = $this->defaultPermissions();

        foreach ($globalPermissionProviders as $provider) {
            $this->categoryList += $provider->getCategories();
            $this->permissionList += $provider->getPermissions();
        }
    }

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
        return array_keys($this->permissionList);
    }

    public function getCssClassForAction(string $action): string
    {
        return $this->permissionList[$action]['cssClass'] ?? self::CSS_CLASS_SUCCESS;
    }

    function getTranslationPrefix(): string
    {
        return "fom.security.resource.installation";
    }


    public function getCategoryList(): array
    {
        return $this->categoryList;
    }

    public function getPermissions(string $category): array
    {
        return array_keys(array_filter($this->permissionList, fn($permission) => $permission['category'] === $category));
    }

    protected function defaultGroups(): array
    {
        return [
            self::CATEGORY_APPLICATION => "mb.terms.application.plural",
            self::CATEGORY_SOURCES => "mb.terms.source.plural",
            self::CATEGORY_PERMISSIONS => "fom.user.userbundle.classes.permissions",
            self::CATEGORY_USERS => "fom.user.userbundle.classes.users",
            self::CATEGORY_GROUPS => "fom.user.userbundle.classes.groups",
            self::CATEGORY_API => "fom.user.userbundle.classes.api",
        ];
    }

    protected function defaultPermissions(): array
    {
        return [
            self::ACTION_CREATE_APPLICATIONS => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.create_applications',
                'category' => self::CATEGORY_APPLICATION,
            ],
            self::ACTION_VIEW_ALL_APPLICATIONS => [
                'cssClass' => self::CSS_CLASS_SUCCESS,
                'label' => 'fom.security.resource.installation.view_all_applications',
                'help' => 'fom.security.resource.installation.view_all_applications_help',
                'category' => self::CATEGORY_APPLICATION,
            ],
            self::ACTION_EDIT_ALL_APPLICATIONS => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.edit_all_applications',
                'help' => 'fom.security.resource.installation.edit_all_applications_help',
                'category' => self::CATEGORY_APPLICATION,
            ],
            self::ACTION_DELETE_ALL_APPLICATIONS => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.delete_all_applications',
                'help' => 'fom.security.resource.installation.delete_all_applications_help',
                'category' => self::CATEGORY_APPLICATION,
            ],
            self::ACTION_OWN_ALL_APPLICATIONS => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.own_all_applications',
                'help' => 'fom.security.resource.installation.own_all_applications_help',
                'category' => self::CATEGORY_APPLICATION,
            ],
            self::ACTION_VIEW_SOURCES => [
                'cssClass' => self::CSS_CLASS_SUCCESS,
                'label' => 'fom.security.resource.installation.view_sources',
                'category' => self::CATEGORY_SOURCES,
            ],
            self::ACTION_CREATE_SOURCES => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.create_sources',
                'category' => self::CATEGORY_SOURCES,
            ],
            self::ACTION_REFRESH_SOURCES => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.refresh_sources',
                'category' => self::CATEGORY_SOURCES,
            ],
            self::ACTION_EDIT_FREE_INSTANCES => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.edit_free_instances',
                'category' => self::CATEGORY_SOURCES,
            ],
            self::ACTION_DELETE_SOURCES => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.delete_sources',
                'category' => self::CATEGORY_SOURCES,
            ],
            self::ACTION_MANAGE_PERMISSION => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.manage_permissions',
                'category' => self::CATEGORY_PERMISSIONS,
            ],
            self::ACTION_VIEW_USERS => [
                'cssClass' => self::CSS_CLASS_SUCCESS,
                'label' => 'fom.security.resource.installation.view_users',
                'category' => self::CATEGORY_USERS,
            ],
            self::ACTION_CREATE_USERS => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.create_users',
                'category' => self::CATEGORY_USERS,
            ],
            self::ACTION_EDIT_USERS => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.edit_users',
                'category' => self::CATEGORY_USERS,
            ],
            self::ACTION_DELETE_USERS => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.delete_users',
                'category' => self::CATEGORY_USERS,
            ],
            self::ACTION_VIEW_GROUPS => [
                'cssClass' => self::CSS_CLASS_SUCCESS,
                'label' => 'fom.security.resource.installation.view_groups',
                'category' => self::CATEGORY_GROUPS,
            ],
            self::ACTION_CREATE_GROUPS => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.create_groups',
                'category' => self::CATEGORY_GROUPS,
            ],
            self::ACTION_EDIT_GROUPS => [
                'cssClass' => self::CSS_CLASS_WARNING,
                'label' => 'fom.security.resource.installation.edit_groups',
                'category' => self::CATEGORY_GROUPS,
            ],
            self::ACTION_DELETE_GROUPS => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.delete_groups',
                'category' => self::CATEGORY_GROUPS,
            ],
            self::ACTION_ACCESS_API => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.access_api',
                'category' => self::CATEGORY_API,
            ],
            self::ACTION_UPLOAD_FILES => [
                'cssClass' => self::CSS_CLASS_DANGER,
                'label' => 'fom.security.resource.installation.upload_files',
                'category' => self::CATEGORY_API,
            ],
        ];
    }
}
