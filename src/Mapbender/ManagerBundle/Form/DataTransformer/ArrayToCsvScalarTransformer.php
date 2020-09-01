<?php


namespace Mapbender\ManagerBundle\Form\DataTransformer;


use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transforms array model data into a single comma-separated string, for form views using a single text field
 * for an editable list of values.
 * Operates on strings. For a version fit for integer numbers
 * @see IntArrayToCsvScalarTransformer
 *
 * @todo: optional empty value filtering
 */
class ArrayToCsvScalarTransformer implements DataTransformerInterface
{
    /** @var bool */
    protected $trim;

    /**
     * @param bool $trim
     */
    public function __construct($trim = true)
    {
        $this->trim = $trim;
    }

    public function transform($value)
    {
        if ($value === null) {
            $value = array();
        }
        if (!is_array($value)) {
            throw new TransformationFailedException('Expected an array');
        }
        return implode(',', $value);
    }

    public function reverseTransform($value)
    {
        if (!is_scalar($value)) {
            throw new TransformationFailedException('Expected a string');
        }
        if ($this->trim) {
            return preg_split('/\s*,\s*/', trim($value));
        } else {
            return explode(',', $value);
        }
    }
}
