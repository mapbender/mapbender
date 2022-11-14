<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Twig\Environment;
use Twig\Error\Error;

class HtmlTwigConstraintValidator extends HtmlConstraintValidator
{
    /** @var Environment */
    protected $twig;

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
            $source = new \Twig\Source($value ?: '', 'input');
            $this->twig->parse($this->twig->tokenize($source));
            $structureValid = true;
        } catch (Error $e) {
            $this->context->addViolation('twig.invalid');
            $this->context->addViolation($e->getMessage());
            $structureValid = false;
        }
        if ($structureValid) {
            $afterTwig = $this->twig->createTemplate($value ?: '')->render($constraint->payload['variables']);
            parent::validate($afterTwig ?: '', $constraint);
        }
    }
}
