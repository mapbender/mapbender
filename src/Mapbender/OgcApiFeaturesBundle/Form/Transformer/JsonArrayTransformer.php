<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Transformer;

use Symfony\Component\Form\DataTransformerInterface;

/**
 * Transforms between a PHP array and a JSON-encoded string for use in a HiddenType form field.
 * Returns null (instead of crashing) when the submitted value is not a valid JSON array.
 */
class JsonArrayTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): string
    {
        return is_array($value) ? json_encode($value) : '';
    }

    public function reverseTransform(mixed $value): ?array
    {
        $decoded = json_decode((string)$value, true);
        return is_array($decoded) ? $decoded : null;
    }
}
