<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * Class TwigConstraint
 *
 * @package Mapbender\CoreBundle\Validator\Constraints
 */
class TwigConstraint extends Constraint
{
    public $message = 'twig.invalid';

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}