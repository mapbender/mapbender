<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\EntityManagerInterface;
use FOM\UserBundle\Entity\Permission;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;

class SubjectDomainUser extends AbstractSubjectDomain
{
    const SLUG = "user";


    public function __construct(private EntityManagerInterface $doctrine, protected bool $isAssignable)
    {

    }

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

    public function getTitle(SubjectInterface $subject): string
    {
        return $subject->getUser()->getUserIdentifier();
    }

    public function getAssignableSubjects(): array
    {
        if (!$this->isAssignable) return [];
        $users = $this->doctrine->getRepository(User::class)->findBy([], ['username' => 'ASC']);
        return array_map(
            fn(User $user) => new AssignableSubject(
                self::SLUG,
                $user->getUserIdentifier(),
                $this->getIconClass(),
                user: $user
            ),
            $users
        );
    }

    public function supports(mixed $subject, ?string $action = null): bool
    {
        return $subject instanceof User;
    }

    public function populatePermission(Permission $permission, mixed $subject): void
    {
        parent::populatePermission($permission, $subject);
        $permission->setUser($subject);
    }
}
