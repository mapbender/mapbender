<?php

namespace FOM\UserBundle\Security\Permission;

class SubjectDomainUser extends AbstractSubjectDomain
{
    const SLUG = "user";

    public function getSlug(): string
    {
        return self::SLUG;
    }
}
