<?php

namespace FOM\UserBundle\Security\Permission;

use Symfony\Component\Security\Core\User\UserInterface;

class SubjectDomainRegistered extends AbstractSubjectDomain
{
    const SLUG = "registered";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function buildWhereClause(?UserInterface $user): ?WhereClauseComponent
    {
        // registered user rules are valid for any registered user
        if ($user !== null) {
            return new WhereClauseComponent("p.subject_domain = '" . self::SLUG . "'");
        }
        return null;
    }
}
