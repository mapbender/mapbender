<?php

namespace Mapbender\CoreBundle\Element\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;


class SearchRouterRouteTransformer implements DataTransformerInterface
{
    public function transform($configuration)
    {
        if (!$configuration) {
            return array();
        }
        $title = !empty($configuration['title']) ? $configuration['title'] : '';
        unset($configuration['title']);
        return array(
            'title' => $title,
            'configuration' => $configuration,
        );
    }

    public function reverseTransform($data)
    {
        if (!$data) {
            return array();
        }
        return ($data['configuration'] ?: array()) + array(
            'title' => $data['title'] ?: '',
        );
    }
}
