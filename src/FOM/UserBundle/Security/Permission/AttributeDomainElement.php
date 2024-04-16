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

    public function matchesPermission(array $permission, string $permissionName, mixed $subject): bool
    {
        /** @var Element $subject */
        return parent::matchesPermission($permission, $permissionName, $subject)
            && $permission["element_id"] === $subject->getId();
    }

}
