<?php

namespace FOM\UserBundle\Security\Permission;

class SubjectDomainGroup extends AbstractSubjectDomain
{
    const SLUG = "group";

    public function getSlug(): string
    {
        return self::SLUG;
    }
}
