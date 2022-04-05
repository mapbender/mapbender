<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class TwigConstraint extends Constraint
{
    public function validatedBy()
    {
        return TwigConstraintValidator::class;
    }
}
