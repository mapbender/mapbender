<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Proxy\Proxy;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Entity\User;
use PHPUnit\Exception;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Permission utility service; registered as 'fom.security.permission_manager'
 *
 * This manager is available as a service and can create/read/update/delete permissions.
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
    )
    {
    }

    private array $cache = [];


    public function isGranted(?UserInterface $user, mixed $resource, string $action): bool
    {
        $permissions = $this->getPermissionsForUser($user);
        if (empty($permissions)) return false;

        $resourceDomain = $this->findResourceDomainFor($resource, $action);
        if ($resourceDomain === null) return false;

        $override = $resourceDomain->overrideDecision($resource, $action, $user, $this);
        if ($override !== null) return $override;

        foreach ($permissions as $permission) {
            if ($resourceDomain->matchesPermission($permission, $action, $resource)) return true;
        }
        return false;
    }


    public function revoke(mixed $subject, mixed $resource, string $action): void
    {
        $this->grant($subject, $resource, $action, false);
    }

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
     * @param ?UserInterface $user
     * @return array{permission: string, resource_domain: string, resource: ?string, element_id: ?int, application_id: ?int}
     */
    protected function getPermissionsForUser(?UserInterface $user): array
    {
        $userIdentifier = $user?->getUserIdentifier();
        if (in_array($userIdentifier, $this->cache)) return $this->cache[$userIdentifier];

        $subjectWhereComponents = [];
        $variables = [];
        foreach ($this->subjectDomains as $subjectDomain) {
            $wrapper = $subjectDomain->buildWhereClause($user);
            if ($wrapper === null) continue;
            $subjectWhereComponents[] = '(' . $wrapper->whereClause . ')';
            $variables = array_merge($variables, $wrapper->variables);
        }

        $sql = 'SELECT p.action, p.resource_domain, p.resource, p.element_id, p.application_id FROM fom_permission p';
        if (count($subjectWhereComponents) > 0) $sql .= ' WHERE (' . implode(' OR ', $subjectWhereComponents) . ")";
        $permissions = $this->em->getConnection()->executeQuery($sql, $variables)->fetchAllAssociative();
        $this->cache[$userIdentifier] = $permissions;
        return $permissions;
    }

    public function findResourceDomainFor(mixed $resource, string $action = null, bool $throwIfNotFound = false): ?AbstractResourceDomain
    {
        foreach ($this->resourceDomains as $resourceDomain) {
            if ($resourceDomain->supports($resource, $action)) return $resourceDomain;
        }

        if ($throwIfNotFound) {
            throw new \InvalidArgumentException("No resource domain registered that can handle resource '$resource' (type " . $resource::class . ")");
        }
        return null;
    }

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
     * @return Permission[]
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

    private function isEntity(string|object $class): bool
    {
        if (is_object($class)) {
            $class = ($class instanceof Proxy)
                ? get_parent_class($class)
                : get_class($class);
        }

        return !$this->em->getMetadataFactory()->isTransient($class);
    }


    /**
     * Save permissions for a resource. Should be called from a controller's "save" method
     * after validating the form data
     * @param mixed $resource
     * @param array{subjectJson: string, permissions: bool[]} $permissionData
     * @param array<string>|null $actionFilter
     * @return void
     */
    public function savePermissions(mixed $resource, array $permissionData, ?array $actionFilter = null): void
    {
        $resourceDomain = $this->findResourceDomainFor($resource, throwIfNotFound: true);
        $availableActions = is_array($actionFilter) ? $actionFilter : $resourceDomain->getActions();

        // TODO: smarter method than deleting old permissions and adding new
        $oldPermissions = $this->findPermissions($resourceDomain, $resource, false, actionFilter: $actionFilter);
        $oldPermissionIds = array_map(fn($p) => $p->getId(), $oldPermissions);
        $this->em->getRepository(Permission::class)->createQueryBuilder('p')
            ->delete()->where('p.id IN (:ids)')->setParameter('ids', $oldPermissionIds)
            ->getQuery()->execute()
        ;

        foreach ($permissionData as $newPermission) {
            $json = json_decode($newPermission['subjectJson'], true);
            for ($i = 0; $i < min(count($newPermission['permissions']), count($availableActions)); $i++) {
                if ($newPermission['permissions'][$i] === true) {
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
        $this->em->flush();
        // invalidate caches after changing access rights
        $this->cache = [];
    }

    public function hasPermissionsDefined(mixed $resource): bool
    {
        $domain = $this->findResourceDomainFor($resource);
        $q = $this->em->getRepository(Permission::class)->createQueryBuilder('p');
        $domain->buildWhereClause($q, $resource);
        $result = $q->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();
        return $result > 0;
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
