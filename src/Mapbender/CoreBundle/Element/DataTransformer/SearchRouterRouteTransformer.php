<?php

namespace Mapbender\CoreBundle\Element\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;


class SearchRouterRouteTransformer implements DataTransformerInterface
{
    public function transform($value): array
    {
        if (!$value) {
            return [];
        }
        $title = !empty($value['title']) ? $value['title'] : '';
        unset($value['title']);
        return [
            'title' => $title,
            'configuration' => $value,
        ];
    }

    public function reverseTransform($value): array
    {
        if (!$value) {
            return [];
        }
        return ($value['configuration'] ?: []) + [
            'title' => $value['title'] ?: '',
        ];
    }
}
