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
            ->add('target', 'Mapbender\CoreBundle\Element\Type\TargetElementType', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            ->add('auto_activate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.redlining.label.auto_activate',
            ))
            ->add('deactivate_on_close', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'mb.core.admin.redlining.label.deactivate_on_close',
            ))
            ->add('geometrytypes', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'multiple' => true,
                'choices' => array(
                    'mb.core.redlining.geometrytype.point' => 'point',
                    'mb.core.redlining.geometrytype.line' => 'line',
                    'mb.core.redlining.geometrytype.polygon' => 'polygon',
                    'mb.core.redlining.geometrytype.rectangle' => 'rectangle',
                    'mb.core.redlining.geometrytype.circle' => 'circle',
                    'mb.core.redlining.geometrytype.text' => 'text',
                ),
                'choices_as_values' => true,
            ))
        ;
    }
}
