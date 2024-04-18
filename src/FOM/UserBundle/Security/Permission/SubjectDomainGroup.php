<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class SubjectDomainGroup extends AbstractSubjectDomain
{
    const SLUG = "group";

    public function getSlug(): string
    {
        return self::SLUG;
    }

    public function __construct(private EntityManagerInterface $doctrine, protected bool $isAssignable)
    {

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


    function getTitle(SubjectInterface $subject): string
    {
        return $subject->getGroup()->getTitle();
    }

    public function getAssignableSubjects(): array
    {
        if (!$this->isAssignable) return [];
        $groups = $this->doctrine->getRepository(Group::class)->findBy([], ['title' => 'ASC']);
        return array_map(
            fn(Group $group) => new AssignableSubject(
                self::SLUG,
                $group->getTitle(),
                $this->getIconClass(),
                group: $group
            ),
            $groups
        );
    }
}
