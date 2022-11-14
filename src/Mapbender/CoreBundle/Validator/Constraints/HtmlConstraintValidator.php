<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

class HtmlConstraintValidator extends TwigConstraintValidator
{
    /**
     * @param string $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        $errorCountBefore = count($this->context->getViolations());
        parent::validate($value, $constraint);
        if (count($this->context->getViolations()) === $errorCountBefore) {
            $afterTwig = $this->twig->createTemplate($value)->render($constraint->payload['variables']);
            try {
                $dom = new \DOMDocument;
                $dom->loadHTML($afterTwig);
            } catch (\Exception $e) {
                // Ignore DOMDocument complaining about empty value
                // see https://www.php.net/manual/en/domdocument.loadhtml.php
                if ($afterTwig) {
                    $this->context->addViolation('html.invalid');
                }
            }
        }
    }
}
