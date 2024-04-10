<?php

namespace FOM\UserBundle\Security\Permission;

abstract class AbstractSubjectDomain
{
    /**
     * returns the unique slug for this subject domain that will be saved
     * in the database's "subject_domain" column within the fom_permission table.
     * Convention: also declare a const string SLUG for easy access
     * @return string
     */
    abstract function getSlug(): string;

}
