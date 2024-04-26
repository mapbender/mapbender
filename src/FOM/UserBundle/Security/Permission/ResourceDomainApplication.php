<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
use Mapbender\CoreBundle\Entity\Application;

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
            && ($action === null || in_array($action, $this->getActions()));
    }

    public function isHierarchical(): bool
    {
        return true;
    }

    public function matchesPermission(array $permission, string $action, mixed $subject): bool
    {
        /** @var Application $subject */

        return parent::matchesPermission($permission, $action, $subject)
            && $permission["application_id"] === $subject->getId();
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
}
