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
        // DOMDocument parsing fails on empty or all-whitespace values
        // Wrap in valid outer tag to work around this
        // see https://www.php.net/manual/en/domdocument.loadhtml.php
        $wrapped = '<div>' . $value . '</div>';
        try {
            $dom = new \DOMDocument;
            $dom->loadHTML($wrapped);
        } catch (\Exception $e) {
            $this->context->addViolation('html.invalid');
        }
    }
}
