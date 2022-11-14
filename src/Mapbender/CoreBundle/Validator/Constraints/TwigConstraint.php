<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class TwigConstraint extends Constraint
{
    public function __construct(array $variables = array())
    {
        parent::__construct(null);
        $this->payload = array(
            'variables' => $variables,
        );
    }

    public function validatedBy()
    {
        return TwigConstraintValidator::class;
    }
}
