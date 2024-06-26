<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Entity\Permission;
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

    public function buildWhereClause(QueryBuilder $q, ?UserInterface $user): void
    {
        if ($user !== null and $user instanceof User) {
            $q->orWhere("p.subjectDomain = '" . self::SLUG . "' AND p.group IN (:groups)")
                ->setParameter('groups', $user->getGroups())
            ;
        }
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

    public function supports(mixed $subject, ?string $action = null): bool
    {
        return $subject instanceof Group;
    }

    public function populatePermission(Permission $permission, mixed $subject): void
    {
        parent::populatePermission($permission, $subject);
        $permission->setGroup($subject);
    }
}
