<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Twig\Environment;
use Twig\Error\Error;

class TwigConstraintValidator extends ConstraintValidator
{
    /** @var Environment */
    private $twig;

    /**
     * @param Environment $twig
     */
    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * @param string $twigString
     * @param Constraint $constraint
     */
    public function validate($twigString, Constraint $constraint)
    {
        try {
            $this->twig->parse($this->twig->tokenize($twigString));
        } catch (Error $e) {
            $this->context->addViolation($constraint->message);
            $this->context->addViolation($e->getMessage());
        }
    }
}
