<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Given input must be both valid twig and valid HTML.
 * (HTML validity checked on twig rendering output)
 */
class HtmlTwigConstraint extends Constraint
{
    public function __construct(array $variables = [])
    {
        parent::__construct(null);
        $this->payload = [
            'variables' => $variables,
        ];
    }

    public function validatedBy(): string
    {
        return HtmlTwigConstraintValidator::class;
    }
}
