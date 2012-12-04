<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mapbender\CoreBundle\Form\Type\ExtentType;

class MapAdminType extends AbstractType {
    public function getName() {
        return 'map';
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'available_templates' => array()));
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('layerset', 'choice', array(
                'label' => 'Layer set',
                'disabled' => true,
                'required' => false))
            ->add('dpi', 'integer', array(
                'label' => 'DPI'
            ))
            ->add('srs', 'text', array(
                'label' => 'Spatial Reference System'
            ))
            ->add('units', 'choice', array(
                'label' => 'Map units',
                'choices' => array(
                    'degrees' => 'Degrees',
                    'm' => 'Meters',
                    'ft' => 'Feet',
                    'mi' => 'Miles',
                    'inches' => 'Inches'
            )))
            ->add('extent_max', new ExtentType(), array(
                'label' => 'Max. extent',
                'property_path' => '[extents][max]'
            ))
            ->add('extent_start', new ExtentType(), array(
                'label' => 'Start. extent',
                'property_path' => '[extents][start]'
            ))
            ->add('maxResolution', 'text', array(
                'label' => 'Max. resolution'
            ))
            ->add('imgPath', 'text', array(
                'label' => 'OpenLayers image path'
            ))
            ->add('otherSrs', 'text', array(
                'label' => 'Other Spatial Reference Systems'
            ));
    }
}

