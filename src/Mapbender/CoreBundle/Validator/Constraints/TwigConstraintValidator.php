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
     * @param string $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        try {
            $source = new \Twig\Source($value, 'input');
            $this->twig->parse($this->twig->tokenize($source));
        } catch (Error $e) {
            $this->context->addViolation('twig.invalid');
            $this->context->addViolation($e->getMessage());
        }
    }
}
