<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

class HtmlConstraint extends TwigConstraint
{
    public function validatedBy()
    {
        return HtmlConstraintValidator::class;
    }
}
