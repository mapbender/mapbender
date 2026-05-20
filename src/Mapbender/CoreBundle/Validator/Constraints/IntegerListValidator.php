<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\SRS;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class IntegerListValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) return;
        $values = explode(',', $value);
        foreach($values as $value) {
            $value = trim($value);
            if (!intval($value) && $value !== '0') {
                $this->context->addViolation('mb.core.map.admin.invalid_integer');
            }
        }
    }
}
