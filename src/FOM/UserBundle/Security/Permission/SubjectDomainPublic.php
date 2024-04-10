<?php

namespace FOM\UserBundle\Security\Permission;

use FOM\UserBundle\Security\Permission\AbstractSubjectDomain;

class SubjectDomainPublic extends AbstractSubjectDomain
{
    const SLUG = "public";

    public function getSlug(): string
    {
        return self::SLUG;
    }

}
