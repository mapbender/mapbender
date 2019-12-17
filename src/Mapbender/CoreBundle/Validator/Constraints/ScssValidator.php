<?php

namespace Mapbender\CoreBundle\Validator\Constraints;

use Assetic\Asset\StringAsset;
use Mapbender\CoreBundle\Asset\CssCompiler;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * @package Mapbender\CoreBundle\Validator\Constraints
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class ScssValidator extends ConstraintValidator
{
    protected $compiler;

    /**
     * @param CssCompiler $compiler
     */
    public function __construct(CssCompiler $compiler)
    {
        $this->compiler = $compiler;
    }

    /**
     * @inheritdoc
     */
    public function validate($value, Constraint $constraint)
    {
        $asset = new StringAsset($value ?: '');

        try {
            $this->compiler->compile(array($asset), true);
        } catch (\Exception $e) {
            $matches = null;
            preg_match("/Error Output:.*?:(\\d+):\\s*(.+?)\\s+Input:/sm", $e->getMessage(), $matches);
            $line         = $matches[1];
            $errorMessage = $matches[2];
            if ($errorMessage == 'invalid property name') {
                $errorMessage = "Brace not closed";
            }
            $this->context->addViolation("Line:$line: $errorMessage", array(), $value);
        }
    }
}
