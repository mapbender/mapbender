<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @package Mapbender\CoreBundle\Validator\Constraints
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 * @Annotation
 */
class Scss extends Constraint
{
    public function validatedBy()
    {
        return 'Mapbender\CoreBundle\Validator\Constraints\ScssValidator';
    }
}
