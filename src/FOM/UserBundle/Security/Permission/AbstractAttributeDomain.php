<?php

namespace FOM\UserBundle\Security\Permission;

abstract class AbstractAttributeDomain
{
    /**
     * returns the unique slug for this attribute domain that will be saved
     * in the database's "attribute_domain" column within the fom_permission table.
     * Convention: also declare a const string SLUG for easy access
     * @return string
     */
    abstract function getSlug(): string;
}
