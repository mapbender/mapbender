<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RulerAdminType extends AbstractType
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
            ->add('type', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType',
                array(
                    'required' => true,
                    'choices' => array(
                        "line" => "line",
                        "area" => "area",
                    ),
                    'choices_as_values' => true,
                ))
            ->add('immediate', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
                'label' => 'Immediate',
            ))
            ->add('strokeWidth', 'Symfony\Component\Form\Extension\Core\Type\IntegerType',
                array('required' => false,
                    'label' => 'Str. Width',
                    'attr' => ['min' => 1, 'max' => 8],
                    'data' => 1))
            ->add('strokeOpacity', 'Symfony\Component\Form\Extension\Core\Type\RangeType',
                array('required' => false, 'label' => 'Str. Opacity', 'attr' => [
                    'min' => 0,
                    'max' => 10
                ], 'data' => 10))
            ->add('strokeColor', 'Symfony\Component\Form\Extension\Core\Type\TextType',
                array('required' => false, 'label' => 'Stroke Color', 'data' => '#ee9900'))
            ->add('fillOpacity', 'Symfony\Component\Form\Extension\Core\Type\RangeType',
                array('required' => false, 'label' => 'Fill Opacity', 'attr' => [
                    'min' => 0,
                    'max' => 10
                ], 'data' => 4))
            ->add('fillColor', 'Symfony\Component\Form\Extension\Core\Type\TextType',
                array(
                    'required' => false,
                    'label' => 'Fill Color',
                    'data' => '#ee9900'));
    }


}
