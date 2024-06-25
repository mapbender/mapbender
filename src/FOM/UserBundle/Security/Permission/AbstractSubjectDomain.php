<?php

namespace FOM\UserBundle\Security\Permission;

use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Permission;
use Mapbender\CoreBundle\Component\IconPackageFa4;
use Mapbender\CoreBundle\Component\IconPackageMbIcons;
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
     * returns the icon css class that should be used for representing this subject in a backend list
     * @see IconPackageFa4::getIconMarkup() or https://fontawesome.com/icons/
     * @see IconPackageMbIcons::getIconMarkup()
     */
    function getIconClass(): string
    {
        return "fas fa-user";
    }

    /**
     * returns the title that should be used for representing this subject in a backend list
     */
    abstract function getTitle(SubjectInterface $subject): string;

    /**
     * Returns all subjects of this domain that are available in this mapbender installation
     * they are used when adding a permission entry
     * @return AssignableSubject[]
     */
    abstract function getAssignableSubjects(): array;

    /**
     * determines if the subject domain applies to a given subject class and (optionally) a given action
     */
    abstract function supports(mixed $subject, ?string $action = null): bool;


    /**
     * Writes all data required to identify the given subject to the given permission entity
     */
    public function populatePermission(Permission $permission, mixed $subject): void
    {
        $permission->setSubjectDomain($this->getSlug());
    }
}
