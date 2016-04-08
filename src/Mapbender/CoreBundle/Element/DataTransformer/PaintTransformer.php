<?php

namespace Mapbender\CoreBundle\Element\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class PaintTransformer implements DataTransformerInterface
{
    public function transform($configuration)
    {
        $data = array_merge_recursive($configuration);
        $data = $this->convertFromOl($data);
        return $data;
    }

    public function reverseTransform($data)
    {
        $conf = array_merge_recursive($data);
        $conf = $this->convertToOl($conf);
        return $conf;
    }

    private function convertToOl($data)
    {
        $tmp = array(
            'fillColor' => $data['fill']['color'],
            'fillOpacity' => floatval($data['fill']['opacity']),
            'strokeColor' => $data['stroke']['color'],
            'strokeOpacity' => floatval($data['stroke']['opacity']),
            'strokeWidth' => intval($data['stroke']['width']),
            'strokeLinecap' => $data['stroke']['linecap'],
            'strokeDashstyle' => $data['stroke']['dashstyle'],
            'pointRadius' => intval($data['point']['radius'])
        );
        return $tmp;
    }

    private function convertFromOl($data)
    {
        $tmp = array(
            'fill' => array(
                'color' => $data['fillColor'],
                'opacity' => $data['fillOpacity']
            ),
            'stroke' => array(
                'color' => $data['strokeColor'],
                'opacity' => $data['strokeOpacity'],
                'width' => $data['strokeWidth'],
                'linecap' => $data['strokeLinecap'],
                'dashstyle' => $data['strokeDashstyle']
            ),
            'point' => array(
                'radius' => $data['pointRadius']
            )
        );
        return $tmp;
    }
}
