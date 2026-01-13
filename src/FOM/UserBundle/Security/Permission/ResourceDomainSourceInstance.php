<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
use Mapbender\CoreBundle\Entity\ReusableSourceInstanceAssignment;
use Mapbender\CoreBundle\Entity\SourceInstance;

class ResourceDomainSourceInstance extends AbstractResourceDomain
{
    const SLUG = "source_instance";

    const ACTION_VIEW = "view";


    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function getActions(): array
    {
        return [
            self::ACTION_VIEW,
        ];
    }

    public function supports(mixed $resource, ?string $action = null): bool
    {
        return ($resource instanceof SourceInstance || $resource instanceof ReusableSourceInstanceAssignment)
            && ($action === null || in_array($action, $this->getActions()));
    }

    public function isHierarchical(): bool
    {
        return false;
    }

    public function matchesPermission(Permission $permission, string $action, mixed $resource): bool
    {
        /** @var SourceInstance|ReusableSourceInstanceAssignment $resource */
        return parent::matchesPermission($permission, $action, $resource)
            && (
                (
                    $permission->getSourceInstance()?->getId() === $resource->getId() &&
                    $resource instanceof SourceInstance
                ) || (
                    $permission->getSharedInstanceAssignment()?->getId() === $resource->getId() &&
                    $resource instanceof ReusableSourceInstanceAssignment
                )
            );
    }

    public function buildWhereClause(QueryBuilder $q, mixed $resource): void
    {
        match (true) {
            $resource instanceof SourceInstance => $q->orWhere("(p.sourceInstance = :sourceInstance AND p.resourceDomain = '" . self::SLUG . "')")
                ->setParameter('sourceInstance', $resource),
            $resource instanceof ReusableSourceInstanceAssignment => $q->orWhere("(p.sharedInstanceAssignment = :sharedInstanceAssignment AND p.resourceDomain = '" . self::SLUG . "')")
                ->setParameter('sharedInstanceAssignment', $resource),
        };
    }

    public function getCssClassForAction(string $action): string
    {
        return self::CSS_CLASS_SUCCESS;
    }

    public function populatePermission(Permission $permission, mixed $resource): void
    {
        /** @var SourceInstance|ReusableSourceInstanceAssignment $resource */
        parent::populatePermission($permission, $resource);
        match (true) {
            $resource instanceof SourceInstance => $permission->setSourceInstance($resource),
            $resource instanceof ReusableSourceInstanceAssignment => $permission->setSharedInstanceAssignment($resource),
        };
    }

    function getTranslationPrefix(): string
    {
        return "fom.security.resource.source_instance";
    }
}
