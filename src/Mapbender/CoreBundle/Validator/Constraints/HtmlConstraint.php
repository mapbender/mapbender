<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class HtmlConstraint
 *
 * @package Mapbender\CoreBundle\Validator\Constraints
 */
class HtmlConstraint extends Constraint
{
    public $message = 'html.invalid';

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}