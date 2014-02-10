<?php

namespace Mapbender\CoreBundle\Element\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;


class SearchRouterRouteTransformer implements DataTransformerInterface
{
    public function transform($configuration)
    {
        $data = array();
        foreach($configuration ?: array() as $key => $value) {
            $title = $value['title'];
            unset($value['title']);
            $data[$key] = array(
                'title' => $title,
                'configuration' => $value
            );
        }

        return $data;
    }

    public function reverseTransform($data)
    {
        $configuration = array();
        foreach($data as $key => $value) {
            $configuration[$key] = $value['configuration'] + array('title' => $value['title']);
        }

        return $configuration;
    }
}
