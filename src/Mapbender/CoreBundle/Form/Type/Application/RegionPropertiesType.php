<?php


namespace Mapbender\CoreBundle\Form\Type\Application;


use Symfony\Component\Form\AbstractType;

class RegionPropertiesType extends AbstractType
{

    public function getName()
    {
        return 'region_properties';
    }

    public function getParent()
    {
        return 'choice';
    }
}
