<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\User\UserInterface;

class ResourceDomainApplication extends AbstractResourceDomain
{
    const SLUG = "application";

    const ACTION_VIEW = "view";
    const ACTION_EDIT = "edit";
    const ACTION_DELETE = "delete";
    const ACTION_MANAGE_PERMISSIONS = "manage_permissions";


    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function getActions(): array
    {
        return [
            self::ACTION_VIEW,
            self::ACTION_EDIT,
            self::ACTION_DELETE,
            self::ACTION_MANAGE_PERMISSIONS
        ];
    }

    public function supports(mixed $resource, ?string $action = null): bool
    {
        return $resource instanceof Application
            && $resource->getSource() === Application::SOURCE_DB
            && ($action === null || in_array($action, $this->getActions()));
    }

    public function isHierarchical(): bool
    {
        return true;
    }

    public function matchesPermission(Permission $permission, string $action, mixed $resource): bool
    {
        /** @var Application $resource */
        return parent::matchesPermission($permission, $action, $resource)
            && $resource->getSource() === Application::SOURCE_DB
            && $permission->getApplication()?->getId() === $resource->getId();
    }

    public function buildWhereClause(QueryBuilder $q, mixed $resource): void
    {
        /** @var Application $resource */
        $q->orWhere("(p.application = :application AND p.resourceDomain = '" . self::SLUG . "')")
            ->setParameter('application', $resource)
        ;
    }

    public function getCssClassForAction(string $action): string
    {
        return match ($action) {
            self::ACTION_VIEW => self::CSS_CLASS_SUCCESS,
            self::ACTION_EDIT => self::CSS_CLASS_WARNING,
            default => self::CSS_CLASS_DANGER,
        };
    }

    public function populatePermission(Permission $permission, mixed $resource): void
    {
        /** @var Application $resource */
        parent::populatePermission($permission, $resource);
        $permission->setApplication($resource);
    }

    function getTranslationPrefix(): string
    {
        return "fom.security.resource.application";
    }

    public function overrideDecision(mixed $resource, string $action, ?UserInterface $user, PermissionManager $manager): bool|null
    {
        // if the user is granted one of the global application rights, this overrides the permissions
        // defined for the individual application

        $globalRightsMap = [
            self::ACTION_VIEW => ResourceDomainInstallation::ACTION_VIEW_ALL_APPLICATIONS,
            self::ACTION_EDIT => ResourceDomainInstallation::ACTION_EDIT_ALL_APPLICATIONS,
            self::ACTION_DELETE => ResourceDomainInstallation::ACTION_DELETE_ALL_APPLICATIONS,
            self::ACTION_MANAGE_PERMISSIONS => ResourceDomainInstallation::ACTION_OWN_ALL_APPLICATIONS,
        ];

        if ($manager->isGranted($user, null, $globalRightsMap[$action])) return true;
        return null;
    }
}
