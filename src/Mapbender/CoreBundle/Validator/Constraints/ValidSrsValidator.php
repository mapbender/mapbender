<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\SRS;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ValidSrsValidator extends ConstraintValidator
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if ($value === null) return;
        /** @var ValidSrs $constraint */
        $values = $constraint->multiple ? explode(',', $value) : [$value];
        foreach($values as $value) {
            $value = trim($value);
            if (!preg_match('/^EPSG:\d+(\|[^,]+)?$/', $value)) {
                $this->context->addViolation('mb.core.map.admin.epsg_invalid_format');
                continue;
            }

            $srsName = explode('|', $value, 2)[0];
            $srs = $this->em->getRepository(SRS::class)->findOneBy(array("name" => $srsName));
            if (!$srs) {
                $this->context->addViolation('mb.core.map.admin.epsg_not_found');
            }
        }
    }
}
