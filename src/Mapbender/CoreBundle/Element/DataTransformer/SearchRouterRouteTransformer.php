<?php

namespace Mapbender\CoreBundle\Element\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;


class SearchRouterRouteTransformer implements DataTransformerInterface
{
    public function transform($value): array
    {
        if (!$value) {
            return array();
        }
        $title = !empty($value['title']) ? $value['title'] : '';
        unset($value['title']);
        return array(
            'title' => $title,
            'configuration' => $value,
        );
    }

    public function reverseTransform($value): array
    {
        if (!$value) {
            return array();
        }
        return ($value['configuration'] ?: array()) + array(
            'title' => $value['title'] ?: '',
        );
    }
}
