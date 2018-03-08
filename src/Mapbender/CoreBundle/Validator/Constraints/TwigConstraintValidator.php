<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

/**
 * Class TwigConstraintValidator
 *
 * @package Mapbender\CoreBundle\Validator\Constraints
 */
class TwigConstraintValidator extends ConstraintValidator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * TwigConstraintValidator constructor.
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator) {
        $this->translator = $translator;
    }

    /**
     * @param string $twigString
     * @param Constraint $constraint
     */
    public function validate($twigString, Constraint $constraint)
    {
        try {
            $twig = new \Twig_Environment();
            $twig->addExtension(
                new TranslationExtension($this->translator)
            );

            $twig->parse($twig->tokenize($twigString));
        } catch (\Twig_Error_Syntax $e) {
            $this->context->addViolation($constraint->message);
            $this->context->addViolation($e->getMessage());
        }
    }
}