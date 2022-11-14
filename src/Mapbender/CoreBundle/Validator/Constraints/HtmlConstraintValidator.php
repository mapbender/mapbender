<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class HtmlConstraintValidator extends ConstraintValidator
{
    /**
     * @param string $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        // Avoid DOMDocument complaining about empty value
        // see https://www.php.net/manual/en/domdocument.loadhtml.php
        if ($value) {
            try {
                $dom = new \DOMDocument;
                $dom->loadHTML($value);
            } catch (\Exception $e) {
                $this->context->addViolation('html.invalid');
            }
        }
    }
}
