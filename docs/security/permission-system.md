# Permission System

Since Mapbender 4 a new permission was introduced to replace the overly complex and deprecated ACL bundle from Symfony.
Most of the code is located at `/src/FOM/UserBundle/Security/Permission`

## Definitions

| Definition          | Explanation                                                                                                                                                                                                                      | 
|---------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------| 
| __Subject__         | The subject defines who a permission is granted to, like a specific user, a group of users or the general public. The subject can be qualified by the _subject domain_ and (optionally) an identifier like the user or group id. | 
| __Subject domain__  | A subject domain describes a class of subjects to whom permissions may be granted. In the core Mapbender, four subject domains are defined: Users, Groups, public access and all registered users                                |  
| __Resource__        | The resource describes to what area of the website a permission is granted, like a specific application. It can be qualified by the _resource domain_ and (optionally) an identifier like the application id.                    | 
| __Resource domain__ | A resource domain describes a class of objects where subjects can get access to. In the core Mapbender, three resource domains are defined: Installation (global access), applications and elements                              |  
| __Action__          | An action or right that can be performed on an object, like `view` or `edit`. The available actions depend on the resource domain.                                                                                               |  
| __Permission__      | A permission is the assignment that grants a specific subject (e.g. the user `Max`) the right to perform a specific action (e.g. `view`) on a specific resource (e.g. the application `map`)                                     |  

By default, a user has no permission on a subject unless it is explicitly granted. Exceptions: Permissions on elements (see below) and the root user (id=1) can always do everything.

## Using the Permission Manager
The central class for the permission system is `FOM\UserBundle\Security\Permission\PermissionManager`. It is registered as a service by `fom.security.permission_manager` or via its FQCN. 
You can customize the permission manager by extending from it and overwriting the parameter `fom.security.permission_manager.class` with your own implementation's FQCN.

The permission manager has the following public methods (for details and parameters look at the [source file](../../src/FOM/UserBundle/Security/Permission/PermissionManager.php)):
- `isGranted`: Checks if a given user has the permission to perform the action on the resource. 
- `grant`, `revoke`: Programmatically grants/revokes a subject to perform an action on a resource
- `findResourceDomainFor`: returns the resource domain for a given resource
- `findSubjectDomainFor`: returns the subject domain for a given subject
- `getAssignableSubjects`: Returns a list of all subjects that are available in this application
- `findPermissions`: Find all permissions that are saved in the database for a given resource domain and resource.
- `getPermissionsForUser`: Gets all permissions for a given user
- `savePermissions`: Save permissions for a resource. Should be called from a controller's "save" method
- `hasPermissionsDefined`: checks if for the given resource there are permission entries defined
- `copyPermissions`: clones all permissions set for the source resource to the target resource

The permission manager is also a symfony [voter](https://symfony.com/doc/current/security/voters.html), therefore it also has the methods `supports` and `voteOnAttribute`

## Predefined Subject Domains
- `public` ([source code](../../src/FOM/UserBundle/Security/Permission/SubjectDomainPublic.php)): Matches everybody, whether logged in or not
- `registered` ([source code](../../src/FOM/UserBundle/Security/Permission/SubjectDomainRegistered.php)): Matches every logged-in user
- `user` ([source code](../../src/FOM/UserBundle/Security/Permission/SubjectDomainUser.php)): Applies for a single user (referenced by its id in the fom_user table)
- `group` ([source code](../../src/FOM/UserBundle/Security/Permission/SubjectDomainGroup.php)): Applies for a group (referenced by its id in the fom_group table)

## Adding custom subject domains
If you want to add a subject domain (e.g. when using LDAP), create a class extending from `FOM\UserBundle\Security\Permission\AbstractSubjectDomain`
and tag it using `fom.security.subject_domain`. Then, overwrite the following methods (for details, look at the [source code](../../src/FOM/UserBundle/Security/Permission/AbstractSubjectDomain.php)):
- `getSlug`: returns the unique slug for this subject domain that will be saved in the database's "subject_domain" column
- `buildWhereClause`: Modify the QueryBuilder using an "orWhere" statement to match the supplied user
- `getIconClass`: returns the icon css class that should be used for representing this subject in a backend list
- `getTitle`: returns the title that should be used for representing this subject in a backend list
- `getAssignableSubjects`: Returns all subjects of this domain that are available in this mapbender installation
- `supports`: determines if the subject domain applies to a given subject class and (optionally) a given action
- `populatePermission`: Writes all data required to identify the given subject to the given permission entity

The subjects will be automatically available when clicking "add" on any permission table. 

## Predefined Resource Domains
- `installation` ([source code](../../src/FOM/UserBundle/Security/Permission/ResourceDomainInstallation.php)): For global permissions like creating applications and deleting sources. When referencing global permissions, the resource is always `null`, e.g. `$this->denyAccessUnlessGranted(ResourceDomainInstallation::ACTION_CREATE_APPLICATIONS);`
- `application` ([source code](../../src/FOM/UserBundle/Security/Permission/ResourceDomainApplication.php)): For permissions applying to a single application. Permissions on an application can be overridden using the installation-wide permissions `view_all_applications`, `edit_all_applications`, `delete_all_applications` and `own_all_applications`
- `element` ([source code](../../src/FOM/UserBundle/Security/Permission/ResourceDomainElement.php)): For permissions applying to a single element. Caution: An element is visible to any user who also has access to the application by default, unless at least one permission is defined for this element, then only the users/groups with dedicated access can view the element.  
- 
## Adding custom resource domains
If you want to add a resource domain (e.g. if you load your applications from a custom source), create a class extending from `FOM\UserBundle\Security\Permission\AbstractResourceDomain`
and tag it using `fom.security.resource_domain`. Then, overwrite the following methods (for details, look at the [source code](../../src/FOM/UserBundle/Security/Permission/AbstractResourceDomain.php)):
- `getSlug`: returns the unique slug for this resource domain that will be saved in the database
- `getActions`: advertises all actions that are valid within this resource domain.
- `getTranslationPrefix`: get the prefix for the translation strings for this resource's actions.
- `isHierarchical`: determines whether the actions available in this resource domain are hierarchical.
- `supports`: determines if the resource domain supports a given resource and (optionally) a given action
- `matchesPermission`: checks if a permission entity applies to the given action and subject
- `buildWhereClause`: Modify the QueryBuilder using an "orWhere" statement to match the supplied resource
- `inheritedActions`: Returns a list of all actions that are implicitly granted if the given action is granted
- `getCssClassForAction`: Returns the css class that will be added to a permission entry in the permission table for the given action
- `populatePermission`: Writes all data required to identify the given resource to the given permission entity
- `overrideDecision`: Override this method if you want to modify the regular behaviour (default deny except a permission is defined for a resource)

To create a form to modify the permissions to your custom resource, inject the PermissionManager and add the following in the edit/create-Method of your controller when setting up your form (after checking if the user has permissions to edit security):

```php
// if you don't have an existing form and want a new form just to edit permissions:
$this->permissionManager->createPermissionForm($myEntity);

// if you have an existing form and want to add the permission list to it:
$this->permissionManager->addFormType($form, $application);

// alternatively, manually:
$resourceDomain = $this->permissionManager->findResourceDomainFor($myEntity, throwIfNotFound: true);
$form->add('security', PermissionListType::class, [
    'resource_domain' => $resourceDomain,
    'resource' => $myEntity,
    'entry_options' => [
        'resource_domain' => $resourceDomain,
    ],
]);
```

Both `addFormType` and `createPermissionForm` accept additional options as an optional parameter that are passed to the PermissionListType. The following options exist:

- `show_public_access` (bool, default: false): If set, the "public access" permission will always be shown even if no rule has been defined
- `allow_add` (bool, default: true): If set to false, no permissions can be added
- `allow_delete` (bool, default: true): If set to false, no permissions can be deleted
- `entry_options.action_filter` (array of strings, default: all actions defined for the resource domain): If set, only the given actions will be shown in the permission list


To persist the permissions, call the following after checking if the form is submitted and valid:

```php
$this->permissionManager->savePermissions($myEntity, $form->get('security')->getData());
```

## Adding global permissions
If you want to add an installation-wide permission that is not dependent on a single resource, create a class implementing the `FOM\UserBundle\Security\Permission\GlobalPermissionProvider` interface and tag it with `fom.security.global_permission`.

The interface has two methods that need to implemented:
- `getCategories()`: Returns the permission categories you want to add to the list that appears when navigating to Security / Global Permissions.
  can be left empty if you only want to extend a category that already exists.  
  The keys should be unique string aliases for the category, the values translation keys for the human-readable values.
- `getPermissions()`: Returns the actual permissions you want to add. The keys are unique (unique across all categories!) string aliases,
  the values should be an array with the following keys:
  - `category`: The alias for the category this permission should be added to
  - `cssClass` (optional, default 'success'): The css class the permission should get when displayed in the backend (background color)

For localisation, use the keys `fom.security.resource.installation.<alias>` resp. `fom.security.resource.installation.<alias>_help` for the help text.

Example:

```php
#[AutoconfigureTag('fom.security.global_permission')
class QueryBuilderPermissionProvider implements GlobalPermissionProvider
{
    const CATEGORY_NAME = "query_builder";
    const PERMISSION_CREATE = "qb_create";
    const PERMISSION_EDIT = "qb_edit";

    public function getCategories(): array
    {
        return [self::CATEGORY_NAME => 'query_builder'];
    }

    public function getPermissions(): array
    {
        return [
            self::PERMISSION_CREATE => [
                'category' => self::CATEGORY_NAME,
                'cssClass' => AbstractResourceDomain::CSS_CLASS_WARNING,
            ],
            self::PERMISSION_EDIT => [
                'category' => self::CATEGORY_NAME,
                'cssClass' => AbstractResourceDomain::CSS_CLASS_WARNING,
            ],
        ];
    }
}
```

If you don't use Autowiring, you need to add the tag definition in XML, for example:

```xml
<service id="mb.querybuilder.permission_provider" class="Mapbender\QueryBuilderBundle\Permission\QueryBuilderPermissionProvider">
    <tag name="fom.security.global_permission" />
</service>
```

## Yaml Applications
Yaml application's security is also stated in the yaml file, therefore the regular PermissionManager can't be used. 
Therefore, the security for yaml application is handled in a [separate voter](../../src/FOM/UserBundle/Security/Permission/YamlApplicationVoter.php).

The following yaml keys are relevant for it's security:
- `published: true`: only used when `roles` is not present. It grants view rights to the public
- `roles`: Can contain the following children:
- public: grants access to the public
- registered: grants access to all registered users:
- users (array): grants access to the given users by username
- groups (array): grants access to the given groups by group title

Example:
```yaml
roles:
  - users:
      - user1
      - user2
  - groups:
      - group1
      - group2
```

[↑ Back to top](#security)

[← Back to README](../README.md)
