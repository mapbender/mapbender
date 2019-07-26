<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class POIAdminType extends AbstractType
{
    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'application' => null,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('tooltip', 'text', array('required' => false))
            ->add('useMailto', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.poi.label.usemailto',
            ))
            ->add('body', 'text', array(
                'required' => true,
            ))
            ->add('gps', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\GpsPosition',
                'application' => $options['application'],
                'property_path' => '[gps]',
                'required' => false,
            ))
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false,
            ))
        ;
    }
}
