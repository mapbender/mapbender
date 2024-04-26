<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

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
        private EntityManagerInterface $doctrineEM,
    )
    {
    }

    private array $cache = [];

    /**
     * @param string $attribute the action
     * @param mixed $subject the resource
     * @return bool
     */
    protected function supports(string $attribute, $subject): bool
    {
        return $this->getResourceDomain($attribute, $subject) !== null;
    }

    /**
     * @param string $attribute the action
     * @param mixed $subject the resource
     * @param TokenInterface $token a wrapper for the logged-in user
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $permissions = $this->getPermissionsForToken($token);
        if (empty($permissions)) return false;

        $resourceDomain = $this->getResourceDomain($attribute, $subject);
        foreach ($permissions as $permission) {
            if ($resourceDomain->matchesPermission($permission, $attribute, $subject)) return true;
        }
        return false;
    }

    /**
     * @param TokenInterface $token
     * @return array{permission: string, resource_domain: string, resource: ?string, element_id: ?int, application_id: ?int}
     */
    protected function getPermissionsForToken(TokenInterface $token): array
    {
        $userIdentifier = $token->getUser()->getUserIdentifier();
        if (in_array($userIdentifier, $this->cache)) return $this->cache[$userIdentifier];

        $subjectWhereComponents = [];
        $variables = [];
        foreach ($this->subjectDomains as $subjectDomain) {
            $wrapper = $subjectDomain->buildWhereClause($token->getUser());
            if ($wrapper === null) continue;
            $subjectWhereComponents[] = '(' . $wrapper->whereClause . ')';
            $variables = array_merge($variables, $wrapper->variables);
        };

        $sql = 'SELECT p.action, p.resource_domain, p.resource, p.element_id, p.application_id FROM fom_permission p';
        if (count($subjectWhereComponents) > 0) $sql .= ' WHERE (' . implode(' OR ', $subjectWhereComponents) . ")";
        $permissions = $this->doctrineEM->getConnection()->executeQuery($sql, $variables)->fetchAllAssociative();
        $this->cache[$userIdentifier] = $permissions;
        return $permissions;
    }

    protected function getResourceDomain(string $action, mixed $resource): ?AbstractResourceDomain
    {
        foreach ($this->resourceDomains as $resourceDomain) {
            if ($resourceDomain->supports($resource, $action)) return $resourceDomain;
        }
        return null;
    }


    /**
     * @return Permission[]
     */
    public function findPermissions(
        AbstractResourceDomain $resourceDomain,
        mixed                  $resource,
        bool                   $group = true,
        bool                   $alwaysAddPublicAccess = false
    ): array
    {
        $repository = $this->doctrineEM->getRepository(Permission::class);
        $query = $repository->createQueryBuilder('p')->select('p');
        $resourceDomain->buildWhereClause($query, $resource);
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
            $tempPermPublicAccess = new Permission();
            $tempPermPublicAccess->setSubjectDomain($this->publicAccessDomain->getSlug());
            $permissionsGrouped = [$this->publicAccessDomain->getSubjectJson() => [$tempPermPublicAccess]] + $permissionsGrouped;
        }

        return $permissionsGrouped;
    }

    public function findResourceDomainFor(mixed $resource): AbstractResourceDomain
    {
        foreach ($this->resourceDomains as $resourceDomain) {
            if ($resourceDomain->supports($resource)) return $resourceDomain;
        }
        throw new \InvalidArgumentException("No resource domain registered that can handle resource '$resource' (type " . $resource::class . ")");
    }

    public function findSubjectDomainFor(Permission $permission): AbstractSubjectDomain
    {
        foreach ($this->subjectDomains as $subjectDomain) {
            if ($subjectDomain->getSlug() === $permission->getSubjectDomain()) return $subjectDomain;
        }
        throw new \InvalidArgumentException("No subject domain registered for '{$permission->getSubjectDomain()}'");
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
     * @param mixed $resource
     * @param array{subjectJson: string, permissions: bool[]} $permissionData
     * @return void
     */
    public function savePermissions(mixed $resource, array $permissionData): void
    {
        $resourceDomain = $this->findResourceDomainFor($resource);
        $availableActions = $resourceDomain->getActions();

        // TODO: smarter method than deleting old permissions and adding new
        $oldPermissions = $this->findPermissions($resourceDomain, $resource, false);
        $oldPermissionIds = array_map(fn($p) => $p->getId(), $oldPermissions);
        $this->doctrineEM->getRepository(Permission::class)->createQueryBuilder('p')
            ->delete()->where('p.id IN (:ids)')->setParameter('ids', $oldPermissionIds)
            ->getQuery()->execute()
        ;

        foreach ($permissionData as $newPermission) {
            $json = json_decode($newPermission['subjectJson'], true);
            for ($i = 0; $i < min(count($newPermission['permissions']), count($availableActions)); $i++) {
                if ($newPermission['permissions'][$i] === true) {
                    $permissionEntity = new Permission();
                    $permissionEntity->setAction($availableActions[$i]);
                    $permissionEntity->setGroup($json["group_id"] ? $this->doctrineEM->getReference(Group::class, $json["group_id"]) : null);
                    $permissionEntity->setUser($json["user_id"] ? $this->doctrineEM->getReference(User::class, $json["user_id"]) : null);
                    $permissionEntity->setSubject($json["subject"]);
                    $permissionEntity->setSubjectDomain($json["domain"]);
                    $resourceDomain->populatePermission($permissionEntity, $resource);
                    $this->doctrineEM->persist($permissionEntity);
                }
            }
        }
        $this->doctrineEM->flush();
    }

    public function grant(mixed $subject, mixed $resource, string $action, bool $isGranted = true)
    {
        // TODO: create this
    }

}
