<?php


namespace Mapbender\CoreBundle\Form\Type\Template;


use Symfony\Component\Form\AbstractType;

class RegionSettingsType extends AbstractType
{
    public function getBlockPrefix()
    {
        return 'region_settings';
    }
}
