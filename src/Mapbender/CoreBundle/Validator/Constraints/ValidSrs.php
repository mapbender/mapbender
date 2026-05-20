<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/** @see ValidSrsValidator */
class ValidSrs extends Constraint
{
    public function __construct(public bool $multiple = false)
    {
        parent::__construct(['multiple' => $multiple], null, null);
    }
}
