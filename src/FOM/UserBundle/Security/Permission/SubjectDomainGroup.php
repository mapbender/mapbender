<?php

namespace FOM\UserBundle\Security\Permission;

use FOM\UserBundle\Entity\User;
use FOM\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\User\UserInterface;

class SubjectDomainGroup extends AbstractSubjectDomain
{
    const SLUG = "group";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function buildWhereClause(?UserInterface $user): ?WhereClauseComponent
    {
        if ($user !== null and $user instanceof User) {
            return new WhereClauseComponent(
                whereClause: "p.subject_domain = '" . self::SLUG . "' AND p.group_id IN (SELECT ug.group_id FROM fom_users_groups ug WHERE ug.user_id = :user_id)",
                variables: ['user_id' => $user->getId()],
            );
        }
        return null;
    }

    function getIconClass(): string
    {
        return "fas fa-users";
    }


    function getTitle(Permission $subject): string
    {
        return $subject->getGroup()->getTitle();
    }
}
