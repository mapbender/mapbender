<?php

namespace FOM\UserBundle\Security\Permission;

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
     * Build an SQL where clause that matches the logged-in user.
     * The permission table is aliased as `p`
     * The logged-in user is available as argument, if you need information from another service, use dependency injection.
     * @param UserInterface|null $user
     * @return ?WhereClauseComponent a wrapper class for the where clause. Variables will be bound using doctrine's bindParam.
     */
    abstract public function buildWhereClause(?UserInterface $user): ?WhereClauseComponent;

}
