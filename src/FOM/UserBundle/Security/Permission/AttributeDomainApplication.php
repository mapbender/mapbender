<?php

namespace FOM\UserBundle\Security\Permission;

class AttributeDomainApplication extends AbstractAttributeDomain
{
    const SLUG = "application";

    const PERMISSION_VIEW = "view";
    const PERMISSION_EDIT = "edit";
    const PERMISSION_DELETE = "delete";
    const PERMISSION_MANAGE_PERMISSIONS = "manage_permissions";

    public function getSlug(): string
    {
        return self::SLUG;
    }
}
