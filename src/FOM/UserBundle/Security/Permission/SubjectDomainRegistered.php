<?php

namespace FOM\UserBundle\Security\Permission;

class SubjectDomainRegistered extends AbstractSubjectDomain
{
    const SLUG = "registered";

    public function getSlug(): string
    {
        return self::SLUG;
    }
}
