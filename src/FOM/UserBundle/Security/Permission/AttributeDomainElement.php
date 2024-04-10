<?php

namespace FOM\UserBundle\Security\Permission;

class AttributeDomainElement extends AbstractAttributeDomain
{
    const SLUG = "element";

    const PERMISSION_VIEW = "view";

    public function getSlug(): string
    {
        return self::SLUG;
    }
}
