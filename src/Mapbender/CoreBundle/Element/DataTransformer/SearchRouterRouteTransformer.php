<?php

namespace Mapbender\CoreBundle\Element\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;


class SearchRouterRouteTransformer implements DataTransformerInterface
{
    public function transform($configuration)
    {
        $title = $configuration['title'];
        unset($configuration['title']);
        return array(
            'title' => $title,
            'configuration' => $configuration,
        );
    }

    public function reverseTransform($data)
    {
        return $data['configuration'] + array(
            'title' => $data['title'],
        );
    }
}
