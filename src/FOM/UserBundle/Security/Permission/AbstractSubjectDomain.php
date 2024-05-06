<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
use Symfony\Component\Security\Core\User\UserInterface;

abstract class AbstractSubjectDomain
{
    /**
     * returns the unique slug for this subject domain that will be saved
     * in the database's "subject_domain" column within the fom_permission table.
     * Convention: also declare a const string SLUG for easy access
     * @return string
     */
    abstract function getSlug(): string;

    /**
     * Modify the QueryBuilder using an "orWhere" statement to match the supplied user
     * The permission table is aliased as `p`
     * The logged-in user is available as argument, if you need information from another service, use dependency injection.
     * @param UserInterface|null $user
     */
    abstract public function buildWhereClause(QueryBuilder $q, ?UserInterface $user): void;

    /**
     * returns the css class that should be used for representing this subject in a backend list
     */
    function getIconClass(): string
    {
        return "fas fa-user";
    }

    /**
     * returns the title that should be used for representing this subject in a backend list
     */
    abstract function getTitle(SubjectInterface $subject): string;

    /** @return SubjectInterface[] */
    abstract function getAssignableSubjects(): array;

    /**
     * determines if the subject domain applies to a given subject class and (optionally) a given action
     * @param mixed|null $subject
     * @param string|null $action
     * @return bool
     */
    abstract function supports(mixed $subject, ?string $action = null): bool;


    public function populatePermission(Permission $permission, mixed $subject): void
    {
        $permission->setSubjectDomain($this->getSlug());
    }
}
