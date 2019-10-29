<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Eslider\Filter\ScssFilter;
use Eslider\ScssAsset;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package Mapbender\CoreBundle\Validator\Constraints
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class ScssValidator extends ConstraintValidator
{
    protected $container;

    /**
     * ContainsAlphanumericValidator constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Checks if the passed value is valid.
     *
     * @param mixed           $value      The value that should be validated
     * @param Scss|Constraint $constraint The constraint for the validation
     */
    public function validate($value, Constraint $constraint)
    {
        if (empty($value)) {
            return;
        }

        try {
            /** @var ScssFilter $sassFilter */
            $sassFilter = $this->container->get("mapbender.assetic.filter.sass");
            $sassFilter->filterLoad(new ScssAsset($value));
        } catch (\Exception $e) {
            $matches = null;
            preg_match("/Error Output:\\s+stdin:(\\d+):\\s*(.+?)\\s+Input:/s", $e->getMessage(), $matches);
            $line         = $matches[1];
            $errorMessage = $matches[2];
            if ($errorMessage == 'invalid property name') {
                $errorMessage = "Brace not closed";
            }
            $this->context->addViolation("Line:$line: $errorMessage", array(), $value);
        }
    }
}
