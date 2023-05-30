<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Given input must be both valid twig and valid HTML.
 * (HTML validity checked on twig rendering output)
 */
class HtmlTwigConstraint extends Constraint
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
        return HtmlTwigConstraintValidator::class;
    }
}
