<?php

namespace FOM\UserBundle\Security\Permission;

use Mapbender\CoreBundle\Entity\Element;

class AttributeDomainElement extends AbstractAttributeDomain
{
    const SLUG = "element";

    const PERMISSION_VIEW = "view";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function supports(string $permission, mixed $subject): bool
    {
        return $subject instanceof Element && in_array($permission, $this->getPermissions());
    }

    public function getPermissions(): array
    {
        return [self::PERMISSION_VIEW];
    }

    public function buildWhereClause(string $permission, mixed $subject): ?WhereClauseComponent
    {
        /** @var Element $subject */
        return new WhereClauseComponent(
            whereClause: "p.permission = :permission AND p.attribute_domain = '".self::SLUG."' AND p.element_id : :element_id",
            variables: ['permission' => $permission, 'element_id' => $subject->getId()]
        );
    }
}
