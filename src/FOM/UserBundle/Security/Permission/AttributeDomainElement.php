<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use Mapbender\CoreBundle\Entity\Element;

class AttributeDomainElement extends AbstractAttributeDomain
{
    const SLUG = "element";

    const PERMISSION_VIEW = "view";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function supports(mixed $subject, ?string $permission = null): bool
    {
        return $subject instanceof Element &&
            ($permission === null || in_array($permission, $this->getPermissions()));
    }

    public function getPermissions(): array
    {
        return [self::PERMISSION_VIEW];
    }

    public function matchesPermission(array $permission, string $permissionName, mixed $subject): bool
    {
        /** @var Element $subject */
        return parent::matchesPermission($permission, $permissionName, $subject)
            && $permission["element_id"] === $subject->getId();
    }

    public function buildWhereClause(QueryBuilder $q, mixed $subject): void
    {
        /** @var Element $subject */
        $q->orWhere("(p.element = :element AND p.attributeDomain = '" . self::SLUG . "')")
            ->setParameter('element', $subject);
    }

}
