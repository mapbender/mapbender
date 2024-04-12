<?php

namespace FOM\UserBundle\Security\Permission;

class WhereClauseComponent
{
    public function __construct(
        public string $whereClause,
        public array  $variables = [],
    )
    {
    }
}
