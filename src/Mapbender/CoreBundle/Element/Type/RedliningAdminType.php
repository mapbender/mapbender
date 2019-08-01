<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RedliningAdminType extends AbstractType
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
            ->add('target', 'target_element', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'property_path' => '[target]',
                'required' => false))
            ->add('display_type', 'choice', array(
                'required' => true,
                'choices' => array(
                    'Dialog' => 'dialog',
                    'Element' => 'element',
                ),
                'choices_as_values' => true,
            ))
            ->add('auto_activate', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.redlining.label.auto_activate',
            ))
            ->add('deactivate_on_close', 'checkbox', array(
                'required' => false,
                'label' => 'mb.core.admin.redlining.label.deactivate_on_close',
            ))
            ->add('geometrytypes', 'choice', array(
                'required' => true,
                'multiple' => true,
                'choices' => array(
                    'Point' => 'point',
                    'Line' => 'line',
                    'Polygon' => 'polygon',
                    'Rectangle' => 'rectangle',
                    'Text' => 'text',
                ),
                'choices_as_values' => true,
            ))
        ;
    }
}
