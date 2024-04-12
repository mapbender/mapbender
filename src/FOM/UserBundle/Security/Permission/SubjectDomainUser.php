<?php

namespace FOM\UserBundle\Security\Permission;

use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class SubjectDomainUser extends AbstractSubjectDomain
{
    const SLUG = "user";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function buildWhereClause(?UserInterface $user): ?WhereClauseComponent
    {
        if ($user !== null and $user instanceof User) {
            return new WhereClauseComponent(
                whereClause: "p.subject_domain = '" . self::SLUG . "' AND p.user_id = :user_id",
                variables: ['user_id' => $user->getId()]
            );
        }
        return null;
    }
}
