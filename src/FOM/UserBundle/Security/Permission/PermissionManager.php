<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Proxy;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Form\Type\PermissionListType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Permission utility service; registered as 'fom.security.permission_manager'
 *
 * This manager is available as a service and can create/read/update/delete permissions.
 * It also doubles as a symfony voter, therefore using `isGranted` and `denyAccessUnlessGranted`
 * in controllers is possible as well
 */
class PermissionManager extends Voter
{
    public function __construct(
        /** @var AbstractResourceDomain[] */
        private array                  $resourceDomains,
        /** @var AbstractSubjectDomain[] */
        private array                  $subjectDomains,
        private ?SubjectDomainPublic   $publicAccessDomain,
        private EntityManagerInterface $em,
        private FormFactory            $formFactory,
    )
    {
    }

    /**
     * @var array<string, Permission[]>
     */
    private array $cache = [];


    /**
     * Checks if a given user has the permission to perform the action on the resource.
     * Hierarchical permissions are considered if applicable for the resource.
     */
    public function isGranted(?UserInterface $user, mixed $resource, string $action): bool
    {
        $resourceDomain = $this->findResourceDomainFor($resource, $action);
        if ($resourceDomain === null) return false;

        $override = $resourceDomain->overrideDecision($resource, $action, $user, $this);
        if ($override !== null) return $override;

        $permissions = $this->getPermissionsForUser($user);
        if (empty($permissions)) return false;

        foreach ($permissions as $permission) {
            if ($resourceDomain->matchesPermission($permission, $action, $resource)) return true;
        }
        return false;
    }


    /**
     * Programmatically grants a subject to perform an action on a resource
     * (or revokes the grant if isGranted is set to false)
     * No permission entry will be added if the exact entry already exists, a grant e.g. to
     * a user who already has access via a group permission will be added however
     */
    public function grant(mixed $subject, mixed $resource, string $action, bool $isGranted = true): void
    {
        $permission = new Permission(action: $action);
        $this->findSubjectDomainFor($subject)->populatePermission($permission, $subject);
        $this->findResourceDomainFor($resource, throwIfNotFound: true)->populatePermission($permission, $resource);

        $existingPermission = $this->em->getRepository(Permission::class)->findOneBy([
            'subjectDomain' => $permission->getSubjectDomain(),
            'user' => $permission->getUser(),
            'group' => $permission->getGroup(),
            'subject' => $permission->getSubject(),
            'resourceDomain' => $permission->getResourceDomain(),
            'element' => $permission->getElement(),
            'application' => $permission->getApplication(),
            'resource' => $permission->getResource(),
            'action' => $permission->getAction(),
        ]);

        if ($existingPermission === null && $isGranted) {
            $this->em->persist($permission);
            $this->em->flush();
        } elseif ($existingPermission !== null && !$isGranted) {
            $this->em->remove($existingPermission);
            $this->em->flush();
        }

        // invalidate caches after changing access rights
        $this->cache = [];
    }

    /**
     * shortcut for grant(isGranted: false)
     * @see self::grant()
     */
    public function revoke(mixed $subject, mixed $resource, string $action): void
    {
        $this->grant($subject, $resource, $action, false);
    }

    /**
     * returns the resource domain for a given resource
     * @see AbstractResourceDomain::supports()
     */
    public function findResourceDomainFor(mixed $resource, ?string $action = null, bool $throwIfNotFound = false): ?AbstractResourceDomain
    {
        foreach ($this->resourceDomains as $resourceDomain) {
            if ($resourceDomain->supports($resource, $action)) return $resourceDomain;
        }

        if ($throwIfNotFound) {
            $resourceString = (is_string($resource) || $resource instanceof \Stringable) ? $resource : '';
            throw new \InvalidArgumentException("No resource domain registered that can handle resource '$resourceString' (type " . $resource::class . ")");
        }
        return null;
    }

    /**
     * returns the subject domain for a given subject
     * @see AbstractSubjectDomain::supports()
     */
    public function findSubjectDomainFor(mixed $permissionOrSubject, string $action = null): AbstractSubjectDomain
    {
        foreach ($this->subjectDomains as $subjectDomain) {
            if ($permissionOrSubject instanceof Permission) {
                if ($subjectDomain->getSlug() === $permissionOrSubject->getSubjectDomain()) return $subjectDomain;
            } elseif ($subjectDomain->supports($permissionOrSubject, $action)) return $subjectDomain;
        }

        if ($permissionOrSubject instanceof Permission) {
            throw new \InvalidArgumentException("No subject domain registered for '{$permissionOrSubject->getSubjectDomain()}'");
        } else {
            throw new \InvalidArgumentException("No subject domain registered that can handle subject '$permissionOrSubject' (type " . $permissionOrSubject::class . ")");
        }
    }

    /**
     * Returns a list of all subjects that are available in this application
     * @return AssignableSubject[]
     */
    public function getAssignableSubjects(): array
    {
        $subjects = [];
        foreach ($this->subjectDomains as $subjectDomain) {
            array_push($subjects, ...$subjectDomain->getAssignableSubjects());
        }
        return $subjects;
    }

    /**
     * Find all permissions that are saved in the database for a given resource domain and resource.
     *
     * @param bool $group if set to true, permission entries are grouped by the subjectJson
     * @param bool $alwaysAddPublicAccess if set to true, a permission entry for public access will always be added,
     * regardless if an entry is saved in the database. If there isn't, a dummy permission entry with a null action will be returned
     * @param string[]|null $actionFilter if given, only permissions for the given actions are returned.
     * The permission hierarchy is ignored, only if a database entry for the exact action exists, the permission will be returned
     * @return Permission[]|array<string, Permission[]> an array of permissions if group==false, otherwise
     * @see SubjectInterface::getSubjectJson()
     */
    public function findPermissions(
        AbstractResourceDomain $resourceDomain,
        mixed                  $resource,
        bool                   $group = true,
        bool                   $alwaysAddPublicAccess = false,
        ?array                 $actionFilter = null,
    ): array
    {
        // querying unpersisted entities results in an error
        if (is_object($resource) && $this->isEntity($resource) && !$this->em->contains($resource)) return [];

        $repository = $this->em->getRepository(Permission::class);
        $query = $repository->createQueryBuilder('p')->select('p');
        $resourceDomain->buildWhereClause($query, $resource);
        if (is_array($actionFilter)) {
            $query->andWhere('p.action IN (:actions)')->setParameter('actions', $actionFilter);
        }
        /** @var Permission[] $permissionsUngrouped */
        $permissionsUngrouped = $query->getQuery()->getResult();

        if (!$group) return $permissionsUngrouped;

        $permissionsGrouped = [];
        foreach ($permissionsUngrouped as $permission) {
            $subjectJson = $permission->getSubjectJson();
            if (!isset($permissionsGrouped[$subjectJson])) $permissionsGrouped[$subjectJson] = [];
            $permissionsGrouped[$subjectJson][] = $permission;
        }

        // for e.g. applications the public access right should always be shown, whether a permission is set or not
        if ($alwaysAddPublicAccess && !$this->publicAccessDomain !== null
            && !array_key_exists($this->publicAccessDomain->getSubjectJson(), $permissionsGrouped)) {
            // add a dummy permission that only has the subject domain set
            $tempPermPublicAccess = new Permission(subjectDomain: $this->publicAccessDomain->getSlug());
            // add public access as first entry
            $permissionsGrouped = [$this->publicAccessDomain->getSubjectJson() => [$tempPermPublicAccess]] + $permissionsGrouped;
        }

        return $permissionsGrouped;
    }

    /**
     * Gets all permissions for a given user. This includes e.g. public permissions and permissions
     * registered for a group the user is part of.
     */
    public function getPermissionsForUser(?UserInterface $user): array
    {
        $userIdentifier = $user?->getUserIdentifier();
        if (array_key_exists($userIdentifier, $this->cache)) return $this->cache[$userIdentifier];

        $q = $this->em->getRepository(Permission::class)->createQueryBuilder('p');
        foreach ($this->subjectDomains as $subjectDomain) {
            $subjectDomain->buildWhereClause($q, $user);
        }

        $permissions = $q->getQuery()->getResult();
        $this->cache[$userIdentifier] = $permissions;
        return $permissions;
    }

    /**
     * Save permissions for a resource. Should be called from a controller's "save" method
     * after validating the form data
     * @param mixed $resource
     * @param array{subjectJson: string, permissions: bool[]} $permissionData an associative array with the subject json
     * as key and a bool array with an entry for each defined action (or each entry in the actionFilter if given)
     * @param array<string>|null $actionFilter if given, only the given actions are processed
     * @see SubjectInterface::getSubjectJson()
     * @see AbstractResourceDomain::getActions() action list for the permission bool array if arrayFilter is not given
     */
    public function savePermissions(mixed $resource, array $permissionData, ?array $actionFilter = null): void
    {
        $resourceDomain = $this->findResourceDomainFor($resource, throwIfNotFound: true);
        $availableActions = is_array($actionFilter) ? $actionFilter : $resourceDomain->getActions();

        $oldPermissions = $this->findPermissions($resourceDomain, $resource, false, actionFilter: $actionFilter);
        // create a map that has the action and subject json of the already defined permission as key
        // to avoid recreating unmodified entries on each save
        $oldPermissionsMap = [];
        foreach ($oldPermissions as $permission) {
            $oldPermissionsMap[$permission->getAction() . $permission->getSubjectJson()] = $permission;
        }

        foreach ($permissionData as $newPermission) {
            $json = json_decode($newPermission['subjectJson'], true);
            for ($i = 0; $i < min(count($newPermission['permissions']), count($availableActions)); $i++) {
                if ($newPermission['permissions'][$i] === true) {
                    $oldPermissionLookupKey = $availableActions[$i] . $newPermission['subjectJson'];
                    if (array_key_exists($oldPermissionLookupKey, $oldPermissionsMap)) {
                        // that permission already existed, skipping. Remove from key to mark it as still present
                        unset($oldPermissionsMap[$oldPermissionLookupKey]);
                        continue;
                    }

                    $permissionEntity = new Permission(
                        subjectDomain: $json["domain"],
                        user: $json["user_id"] ? $this->em->getReference(User::class, $json["user_id"]) : null,
                        group: $json["group_id"] ? $this->em->getReference(Group::class, $json["group_id"]) : null,
                        subject: $json["subject"],
                        action: $availableActions[$i],
                    );
                    $resourceDomain->populatePermission($permissionEntity, $resource);
                    $this->em->persist($permissionEntity);
                }
            }
        }

        // remove permissions that are still in the map, they are not in the list anymore
        foreach ($oldPermissionsMap as $permission) {
            $this->em->remove($permission);
        }

        $this->em->flush();
        // invalidate caches after changing access rights
        $this->cache = [];
    }

    /**
     * checks if for the given resource there are permission entries defined
     */
    public function hasPermissionsDefined(mixed $resource): bool
    {
        $domain = $this->findResourceDomainFor($resource, throwIfNotFound: true);
        $q = $this->em->getRepository(Permission::class)->createQueryBuilder('p');
        $domain->buildWhereClause($q, $resource);
        $result = $q->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        return $result > 0;
    }

    /**
     * clones all permissions set for the source resource to the target resource
     */
    public function copyPermissions(mixed $sourceResource, mixed $targetResource, bool $allowClassMismatch = false): void
    {
        if (!$allowClassMismatch && ClassUtils::getClass($sourceResource) !== ClassUtils::getClass($targetResource)) {
            throw new \InvalidArgumentException("source and target resource must be of the same type");
        }
        $resourceDomain = $this->findResourceDomainFor($sourceResource, throwIfNotFound: true);
        $permissions = $this->findPermissions($resourceDomain, $sourceResource, group: false);
        foreach ($permissions as $permission) {
            $newPermission = new Permission(
                subjectDomain: $permission->getSubjectDomain(),
                user: $permission->getUser(),
                group: $permission->getGroup(),
                subject: $permission->getSubject(),
                action: $permission->getAction(),
            );
            $resourceDomain->populatePermission($newPermission, $targetResource);
            $this->em->persist($newPermission);
        }
        $this->em->flush();
    }

    /**
     * moves all permissions set for the source resource to the target resource
     * @param bool $allowClassMismatch use this cautiously, this just calls populatePermission on the target resource's
     * resource domain, which may not clear existing references
     */
    public function movePermissions(mixed $sourceResource, mixed $targetResource, bool $allowClassMismatch = false): void
    {
        if (!$allowClassMismatch && ClassUtils::getClass($sourceResource) !== ClassUtils::getClass($targetResource)) {
            throw new \InvalidArgumentException("source and target resource must be of the same type");
        }
        $resourceDomain = $this->findResourceDomainFor($sourceResource, throwIfNotFound: true);
        $permissions = $this->findPermissions($resourceDomain, $sourceResource, group: false);
        foreach ($permissions as $permission) {
            $resourceDomain->populatePermission($permission, $targetResource);
        }
        $this->em->flush();
    }

    /**
     * Creates a new symfony form for editing permissions for a given resource
     * to extend existing forms @see self::addFormType()
     */
    public function createPermissionForm(mixed $resource, array $options = []): FormInterface
    {
        $form = $this->formFactory->create(FormType::class, null, array(
            'label' => false,
        ));
        $this->addFormType($form, $resource, $options);
        return $form;
    }

    /**
     * Adds a new form child to an existing Symfony form for editing permissions for a given resource
     */
    public function addFormType(FormInterface $form, mixed $resource, array $options = []): void
    {
        $resourceDomain = $this->findResourceDomainFor($resource, throwIfNotFound: true);
        $form->add('security', PermissionListType::class, array_merge_recursive([
            'resource_domain' => $resourceDomain,
            'resource' => $resource,
            'entry_options' => [
                'resource_domain' => $resourceDomain,
            ],
        ], $options));
    }


    private function isEntity(string|object $class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        }

        return !$this->em->getMetadataFactory()->isTransient($class);
    }



    /** START Wrapper for symfony's VoterInterface */

    /**
     * @param string $attribute the action
     * @param mixed $subject the resource
     * @return bool
     */
    protected function supports(string $attribute, $subject): bool
    {
        return $this->findResourceDomainFor($subject, $attribute) !== null;
    }

    /**
     * @param string $attribute the action
     * @param mixed $subject the resource
     * @param TokenInterface $token a wrapper for the logged-in user
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        return $this->isGranted($user, $subject, $attribute);
    }

    /** END Wrapper for symfony's VoterInterface */


}
