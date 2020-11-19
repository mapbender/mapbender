## v3.3 WIP @ 4dd8e81
- Removed:
  - DoctrineHelper class. Never update your schema in a live session. Always use `app/console doctrine:schema:update`.
  - UserHelper class. Use service `fom.user_helper.service`.
  - ManagerBundle class. Extend `Mapbender\ManagerBundle\Component\ManagerBundle` directly instead.
  - RolesService class, `fom_roles` registration. No invocations im Mapbender codebase. Update / cut any usages assuming calls to `getRoles` and `getAll` return empty arrays.
  - Custom AclEntry entity class. Work with [Acl](https://github.com/symfony/security-acl/blob/2.8/Domain/Acl.php) and [Acl Entry](https://github.com/symfony/security-acl/blob/2.8/Domain/Entry.php) objects provided by Symfony directly.
  - AclManager::getObjectAclEntriesAsArray (no invocations in Mapbender codebase; AclEntry removal collateral)
  - AclManager::getObjectAclEntries (no invocations in Mapbender codebase)
  - AclManager::hasObjectAclEntries (no invocations in Mapbender codebase)
  - FOMIdentitiesProvider::getUsers (no invocations in Mapbender codebase)
  - FOMIdentitiesProvider::getRoles (no invocations in Mapbender codebase)
  - Extension configuration `profile_assets`. If really required, load assets from twig template.

## v3.2.14
- Fix incompatibility with swiftmailer/swiftmailer 6 (registration / password reset)
- Properly declare symfony/swiftmailer-bundle dependency

## v3.2.13
- Fix user password self-editing for low-privilege users

## v3.2.12
- Fix user creation for non-root / users without global `OWNER` grant on user
- Fix support for configurations with empty user profile entity setting
- Offer privilege assignment to all logged-in users (`ROLE_USER`)
- Add [configuration parameters](./src/FOM/UserBundle/CONFIGURATION.md) for suppressing / exposing users, groups, logged-in users and anonymous users when making new privilege assignments.
- Anonymous users now default to not available for assigning privileges; if you require this (unadvisable), set parameter `fom.acl_assignment.show_anonymous` to `true`
- User index: do not link to group editing if current user lacks editing privileges
- User index: suppress group listing if current user lacks viewing privileges
- Relabel profile entry "role" to "position" to disambiguate from grants context
- Improve support for customized user profile entity classes

## v3.2.11
- [regression] Fix error for user without group editing privileges when editing self or other user
- Fix bad grants check for group index menu item: require VIEW, not CREATE
- Fix bad grants check for ACL menu item: require EDIT, ignore CREATE
- Fix invalid potential null return from User::__toString
- Eliminate same-route menu item duplicate "User control" vs "Users"
- Consistently enforce password constraint (default: 8 character minimum length) in all areas
  where passwords can be set: registration, password reset, user creation, user (self-)editing
- Extract user password constraints into user helper service to support DI customization
- Support translation of password repeat mismatch validation error message; supply German and English messages

## v3.2.10
- Fix form type incompatibilities with Symfony 3, allow installation with Symfony 3
- Suppress dangling "Groups" label in user editing if no groups found in database
- Make `fom_core` extension configuration values `mail_from_address` and `mail_from_name` optional; dummy values are
  no longer required. Empty `mail_from_address` will disable mailer-dependent functionality.
- Make user registration and password reset also dependent on non-empty `mail_from_address`
- In debug mode, show more helpful exception messages when requesting disabled registration / password reset urls
- Added [UserBundle/CONFIGURATION.md](https://github.com/mapbender/fom/blob/master/src/FOM/UserBundle/CONFIGURATION.md)
- Remove unreachable FOMGroupsType (unused since v3.0.5.4)
- Remove unusable `FakeGeometryType` class (`SELECT 1` never was a valid column type clause)
- Remove misc deprecated HTML attributes (table `cellspacing` etc)

## v3.2.9
- Do not require ext-ldap methods unless ldap access is configured
- Fix PHP5.3 incompatibility in LDAP UserProvider

## v3.2.8
- Remove assigned ACEs when removing user or group
- Autofocus on filter input in user / group selection

## v3.2.7
- Added `ldap_user_filter` parameter and other configurability related to ACLs and LDAP (see [PR#54](https://github.com/mapbender/fom/pull/54))
- Guard against empty tokens when initializing owner ACE
- Group entity now implements standard RoleInterface

## v3.2.6
- [v3.2.4 regression] fix backlinks to login action generated in forgotten password / registration areas

## v3.2.5
- Fix ACL prefetching error when no users have any assigned ACLs yet

## v3.2.4
- Fix registration process token lookup
- Use translated labels for password reset form fields
- ACLType: support standard_anon_access=true option even with create_standard_permissions=false
- ACLType: support new `aces` option to pass in desired ACE data explicitly
- Optimize grants check performance on large user lists
- Remove some identically copy&pasted css block overrides for easier twig customization

## v3.2.3
- Remove dangling nonfunctional collection item add icon under ACL widgets with empty ACE content

## v3.2.2
- Remove redundant css asset block overrides from password reset / registration templates
- Fix regression saving class ACEs

## v3.2.1
- Fix error in password reset process ([mapbender #1174](https://github.com/mapbender/mapbender/issues/1174))
- Typo fixes ([PR#53](https://github.com/mapbender/fom/pull/53))
- Replace dummy administrator email placeholder in registration reset action with configured sender adress
- Add missing administrator email to all registration and password reset actions
- Misc deprecations and deprecation cleanups

## v3.2.0
- Remove LoginController and related templates (migrated to Mapbender)

## v3.1.13
- Fix user password self-editing for low privilege users
- Fix incompatibility with swiftmailer/swiftmailer 6 (registration / password reset)
- Properly declare symfony/swiftmailer-bundle dependency

## v3.1.12
- Fix user creation for non-root / users without global `OWNER` grant on user
- Fix support for configurations with empty user profile entity setting
- Offer privilege assignment to all logged-in users (`ROLE_USER`)
- Add [configuration parameters](./src/FOM/UserBundle/CONFIGURATION.md) for suppressing / exposing users, groups, logged-in users and anonymous users when making new privilege assignments.
- Anonymous users now default to not available for assigning privileges; if you require this (unadvisable), set parameter `fom.acl_assignment.show_anonymous` to `true`
- User index: do not link to group editing if current user lacks editing privileges
- User index: suppress group listing if current user lacks viewing privileges
- Relabel profile entry "role" to "position" to disambiguate from grants context
- Improve support for customized user profile entity classes

## v3.1.11
- [regression] Fix error for user without group editing privileges when editing self or other user
- Fix bad grants check for group index menu item: require VIEW, not CREATE
- Fix bad grants check for ACL menu item: require EDIT, ignore CREATE
- Fix invalid potential null return from User::__toString
- Eliminate same-route menu item duplicate "User control" vs "Users"
- Consistently enforce password constraint (default: 8 character minimum length) in all areas
  where passwords can be set: registration, password reset, user creation, user (self-)editing
- Extract user password constraints into user helper service to support DI customization
- Support translation of password repeat mismatch validation error message; supply German and English messages

## v3.1.10
- Fix form type incompatibilities with Symfony 3, allow installation with Symfony 3
- Suppress dangling "Groups" label in user editing if no groups found in database
- Make `fom_core` extension configuration values `mail_from_address` and `mail_from_name` optional; dummy values are
  no longer required. Empty `mail_from_address` will disable mailer-dependent functionality.
- Make user registration and password reset also dependent on non-empty `mail_from_address`
- In debug mode, show more helpful exception messages when requesting disabled registration / password reset urls
- Added [UserBundle/CONFIGURATION.md](https://github.com/mapbender/fom/blob/release/3.1/src/FOM/UserBundle/CONFIGURATION.md)
- Remove unreachable FOMGroupsType (unused since v3.0.5.4)
- Remove unusable `FakeGeometryType` class (`SELECT 1` never was a valid column type clause)
- Remove misc deprecated HTML attributes (table `cellspacing` etc)

## v3.1.9
- Do not require ext-ldap methods unless ldap access is configured
- Fix PHP5.3 incompatibility in LDAP UserProvider

## v3.1.8
- Remove assigned ACEs when removing user or group
- Autofocus on filter input in user / group selection

## v3.1.7
- Added `ldap_user_filter` parameter and other configurability related to ACLs and LDAP (see [PR#54](https://github.com/mapbender/fom/pull/54))
- Guard against empty tokens when initializing owner ACE
- Group entity now implements standard RoleInterface

## v3.1.6
- Fix ACL prefetching error when no users have any assigned ACLs yet

## v3.1.5
- Fix registration process token lookup
- Use translated labels for password reset form fields
- ACLType: support standard_anon_access=true option even with create_standard_permissions=false
- ACLType: support new `aces` option to pass in desired ACE data explicitly
- Optimize grants check performance on large user lists
- Remove some identically copy&pasted css block overrides for easier twig customization

## v3.1.4
- Remove dangling nonfunctional collection item add icon under ACL widgets with empty ACE content

## v3.1.3
- Fix regression saving class ACEs

## v3.1.2
- Fix error in password reset process ([mapbender #1174](https://github.com/mapbender/mapbender/issues/1174))
- Typo fixes ([PR#53](https://github.com/mapbender/fom/pull/53))
- Replace dummy administrator email placeholder in registration reset action with configured sender adress
- Add missing administrator email to all registration and password reset actions
- Misc deprecations and deprecation cleanups

## v3.1.1
- Hotfix layouts of registration / forgotten password sections
- Misc deprecation cleanups

## v3.1.0
  - Removed legacy component `PathHelper` (service id `fom.pathhelper`)
  - Removed legacy component `GeoConverter` (serivce id `geo.converter`)
  - Remove Controller, Components, views and JavaScript assets now absorbed into Mapbender ([PR#52](https://github.com/mapbender/fom/pull/52))

## v3.0.6.6
* Fix invalid potential null return from User::__toString
* Fix incompatibility with swiftmailer/swiftmailer 6 (registration / password reset)
* Properly declare symfony/swiftmailer-bundle dependency

## v3.0.6.5
- Make `fom:user:resetroot` command work without (undeclared dependency) sensio/generator-bundle, or with sensio/generator-bundle >= 2.5
- Remove unusable `FakeGeometryType` class (`SELECT 1` never was a valid column type clause)

## v3.0.6.4
  - [Regression] fix broken user privilege editing


## v3.0.6.3
  - Implement `__toString-Method` for FOM/User ([PR#51](https://github.com/mapbender/fom/pull/51))
  - Change URL generation for improved reverse-proxy compatibility ([PR#49](https://github.com/mapbender/fom/pull/49))
  - Fix dropdown value error on Safari ([PR#48](https://github.com/mapbender/fom/pull/48))
  - Add Italian translations ([PR#47](https://github.com/mapbender/fom/pull/47))
  - Misc Symfony deprecation cleanups

## v3.0.6.2
  - [Translation] Added contributed FR translations; thanks to Patrice Pineault!
  - [Translation] Updated NL locale translations; thanks to Just van den Broecke!
  - Converted translation catalogs from XLIFF to Yaml
  - Add cookie consent support to login form ([PR#46](https://github.com/mapbender/fom/pull/46))
  - [Framework] Avoid replacing existing Mapbender-namespace widgets (Autocomplete, Popup2; see [9fd9622](https://github.com/mapbender/fom/commit/9fd96228335f075d2cf3733688ccc0b975b351e1))

## v3.0.6.1
  - Fix SSPI not working anymore
  - Fix second entity manager definition in mapbender not working anymore
  - Fix authentication against OracleDB not working
  - Fix deprecation in FailedLoginListener
  - Fix twig errors when using Form/fields template in frontend (e.g. SearchRouter)
  - Improve ACL handling
  - Improve LDAP user authentication
  - Fix autocomplete.js behavior
  - Fix pasword resend confirmation view
  - Merge pull request #24 from mapbender/hotfix/dropdown-scrolls-background
  - Merge pull request #22 from mapbender/hotfix/sspi_authentication_fix
  - Merge pull request #28 from mapbender/hotfix/fix-visual-form-bugs

## v3.0.6.0
  - Merge feature/symfony-upgrade-2.8
  - Merge release/3.0.5
  - Clean up popup.js documentation
  - Fix autocomplete.js get local name variable instead of global one
  - Fix use SecurityContext by ACEType and ACLType
  - Merge @hwbllmnn symfony 2.7 upgrade branch
  - Merge remote-tracking branch 'composer/feature/symfony2.7' into release/3.0.6
  - Add fullscreen template region "align" and "closed" properties
  - Clean up LoginController, PasswordController and UserController
  - Remove IE 6-10 template support
  - Remove jquery-1.7.1.js and jquery-ui-1.8.16.min.js
  - Remove ACLSIDHelper
  - Remove old binded jquery libraries
  - Remove and refactor LoginController, PasswordController and UserController imports
  - Added back comments
  - Added LDAP Binding for requests. Added some comments and fixed indentation.
  - Refactor and describe RolesService
  - Merge pull request #26 from mapbender/hotfix/ldap_integration_bundle
  - Fix for LDAP user autorization. Rename to correct entity class.
  - Remove LDAP components to Mapbender/Ldap Bundle as composer 'mapbender/ldap' module
  - Merge pull request #24 from mapbender/hotfix/dropdown-scrolls-background
  - Merge pull request #25 from mapbender/fix/acl-handling
  - Remove and unregister LDAP from FOM
  - Fix internal issue #7093
  - Merge pull request #22 from mapbender/hotfix/sspi_authentication_fix
  - Merge pull request #23 from mapbender/fix/route-name
  - Add names for ACL-routes in ACLController
  - Make login, register, forgot password and restore password screens responsive
  - Fixed Sspi-User Authentication
  - Merge pull request #21 from mapbender/hotfix/accordion-in-accordion
  - Fix 'active' by accordion in accordion
  - Fix set admin page title default
  - Merge pull request #20 from mapbender/hotfix/changelog
  - Intergrate bootstrap and refactor/fix administration SCSS files
  - Fix/Remove displaying pasword resend confirmation screen
  - Fix and refactor login and manager template
  - Fix deprecated call by AnnotatedRouteControllerLoader route  configuring

## v3.0.5.4
  - Fix twig errors when using Form/fields template in frontend (e.g. SearchRouter)
  - Improve ACL handling
  - Improve LDAP user authentication
  - Fix autocomplete.js behavior
  - Make login, register, forgot password and restore password screens responsive
  - Fix and refactor login and manager template to use mapbender asset pipeline
  - Fix pasword resend confirmation view
  - Merge pull request #24 from mapbender/hotfix/dropdown-scrolls-background
  - Merge pull request #22 from mapbender/hotfix/sspi_authentication_fix
  - Merge pull request #28 from mapbender/hotfix/fix-visual-form-bugs
  - Merge pull request #44 from mapbender/hotfix/OracleACLListener305
  - Intergrate bootstrap and refactor/fix administration SCSS files
  - Fix deprecated call by AnnotatedRouteControllerLoader route configuring
  - Restrict move popups outside of visible area application
  - Merge pull request #19 from mapbender/hotfix/stored-xss
  - fixed dropdown part of vulnerability
  - Merge hotfix/fix-travis-ci
  - Short user name russian translation
  - Deprecate FOM SharedApplicationWebTestCase
  - Improve tab navigation to use keyboard (TAB)
  - Add ability to see which security permissions are set for an element (or some other object)
  - Extract administration border radius variables
  - Improve login box screen
  - Improve application list navigation
  - Fix embedded login screen if session time is out
  - Improve DoctrineHelper to get create tables for new entities if connection is sqlite
  - Fix xls ExportResponse decode utf-8

## v3.0.5.3
  - Improve reset form styles
  - Fix reset password page styling
  - Fix add user group with same prefix
  - Fix select element
  - Fix add group with same prefix in security tab
  - Fix select element global listener
  - Improve scale and srs selector styles
  - Fix FOM composer.json error
  - Merge pull request #18 from mapbender/hotfix/user-activate
  - Update messages.ru.xlf
  - add 'de' translations
  - translate default fos messages, reformate code
  - Merge branch 'release/3.0.5' into hotfix/user-activate
  - Fix reset password email body text
  - 5190 change format of forgot password mail
  - translation typo de
  - Merge branch 'hotfix/user-activate' into release/3.0.5
  - fix activate/deactivate only other users
  - add aktivate a self registrated user
  - Merge pull request #17 from mapbender/hotfix/changelog-5489
  - added changelog.md information

## v3.0.5.2
  - Add missed 'Bad credentials' translations for ES, NL, PT #5009
  - Add 'Bad credentials' translating and fix some erroneous russian translations #5009
  - change message
  - formate code, merge message
  - Refactor and remove old properties from FOM/UserBundle/Entity/User
  - fix error flash, formate code
  - Fix checking log in request Closes: #4874, #4885
  - Fix authors
  - Add composer.json file

## v3.0.5.1
  - fixed removing of groups
  - fixed filtering of users to keep group info visible
  - added profile form validation
  - fixed delete user
  - backported acl commit fix
  - github #307 update some missing german translations
  - add fom ru translations

## v3.0.5.0
  -  fixed aclmanager reference
  -  fixed file name wrt class name
  -  removed deprecated composer option from .travis.yml
  -  added more portuguese translations
  -  fix 'uid' for MySQL
  -  use admin email from configuration
  -  fixed saving of own user data
  -  fixed own access rights for self registered users
  -  do not assume all people use ldap
  -  fixed registration page layout
  -  do not allow editing of username for normal users
  -  fixed texts for user/group backend
  -  added descriptive text to password reset form
  -  fixed overlapping icons in group user table
  -  mark skel.html.twig as deprecated
  -  use FontAwesomem from composer components in manager.html.twig
  -  use assets from composer components in skel.html.twig
  -  remove using exCanvas for IE8
  -  fix add view port meta configuration
  -  change mobile screen scale dpi=240
  -  added nl translations
  -  fix permissions before login
  -  remove LdapUser ORM annotation
  -  fix entity ldapuser
  -  extend GeoConverterComponent
  -  update ExportResponse.php
  -  extend LdapUser annotations
  -  add triggering "ready" state to the tabcontainer (accordion)
  -  add GeoConverterComponent as "geo.converter" service to convert geometries
  -  extend tabcontainer.js with select method
  -  add hasProfile method to User
  -  Fixing acl apply for ladp users
  -  PQ-22: fixed error in twig with ldap user
  -  add isAnonymous method to User
  -  Adding LDAP User entity and make profile page optional
  -  remove filter with compass from twigs
  -  remove filter with compass from twigs
  -  fix using entity manager
  -  fix merge error
  -  fix twigs
  -  fix UserProfileListener entityManager using
  -  fix UserController; UserProfileListener; UserSubscriber
  -  fix back office template to get SCSSC  work
  -  fix side pane
  -  Check DB platform for profile uid column name
