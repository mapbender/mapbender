<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Class TwigConstraintValidator
 *
 * @package Mapbender\CoreBundle\Validator\Constraints
 */
class TwigConstraintValidator extends ConstraintValidator
{
    /**
     * @param string $twigString
     * @param Constraint $constraint
     */
    public function validate($twigString, Constraint $constraint)
    {
        try {
            $twig = new \Twig_Environment();
            $twig->parse($twig->tokenize($twigString));
        } catch (\Twig_Error_Syntax $e) {
            $this->context->addViolation($constraint->message);
            $this->context->addViolation($e->getMessage());
        }
    }
}