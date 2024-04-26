<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
use Mapbender\CoreBundle\Entity\Element;

class ResourceDomainElement extends AbstractResourceDomain
{
    const SLUG = "element";

    const ACTION_VIEW = "view";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function supports(mixed $resource, ?string $action = null): bool
    {
        return $resource instanceof Element &&
            ($action === null || in_array($action, $this->getActions()));
    }

    public function getActions(): array
    {
        return [self::ACTION_VIEW];
    }

    public function matchesPermission(array $permission, string $action, mixed $subject): bool
    {
        /** @var Element $subject */
        return parent::matchesPermission($permission, $action, $subject)
            && $permission["element_id"] === $subject->getId();
    }

    public function buildWhereClause(QueryBuilder $q, mixed $resource): void
    {
        /** @var Element $resource */
        $q->orWhere("(p.element = :element AND p.resourceDomain = '" . self::SLUG . "')")
            ->setParameter('element', $resource);
    }

    public function populatePermission(Permission $permission, mixed $resource): void
    {
        /** @var Element $resource */
        parent::populatePermission($permission, $resource);
        $permission->setElement($resource);
    }


    function getTranslationPrefix(): string
    {
        return "fom.security.resource.element";
    }

}
