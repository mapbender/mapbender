<?php

namespace FOM\UserBundle\Security\Permission;

use Symfony\Component\Security\Core\User\UserInterface;

class SubjectDomainPublic extends AbstractSubjectDomain
{
    const SLUG = "public";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function buildWhereClause(?UserInterface $user): WhereClauseComponent
    {
        return new WhereClauseComponent("p.subject_domain = '" . self::SLUG . "'");
    }
}
