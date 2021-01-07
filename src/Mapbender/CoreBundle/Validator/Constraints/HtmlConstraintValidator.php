<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class HtmlConstraintValidator
 *
 * @package Mapbender\CoreBundle\Validator\Constraints
 */
class HtmlConstraintValidator extends ConstraintValidator
{
    /**
     * @param string $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        try {
            $dom = new \DOMDocument;
            $dom->loadHTML($value);
        } catch (\Exception $e) {
            $this->context->addViolation($constraint->message);
        }
    }
}