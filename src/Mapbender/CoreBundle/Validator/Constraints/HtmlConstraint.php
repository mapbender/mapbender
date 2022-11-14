<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class HtmlConstraint extends Constraint
{
    public function validatedBy()
    {
        return HtmlConstraintValidator::class;
    }
}
