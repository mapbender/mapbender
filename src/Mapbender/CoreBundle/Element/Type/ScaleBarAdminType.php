<?php
namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScaleBarAdminType extends AbstractType
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
        // @todo: add missing field labels
        $builder
            ->add('target', 'Mapbender\CoreBundle\Element\Type\TargetElementType', array(
                'element_class' => 'Mapbender\\CoreBundle\\Element\\Map',
                'application' => $options['application'],
                'required' => false,
            ))
            // @todo: should be an optional positive integer
            ->add('maxWidth', 'Symfony\Component\Form\Extension\Core\Type\TextType')
            ->add('anchor', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                "choices" => array(
                    'left-top' => 'left-top',
                    'left-bottom' => 'left-bottom',
                    'right-top' => 'right-top',
                    'right-bottom' => 'right-bottom',
                ),
                'choices_as_values' => true,
            ))
            ->add('units', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'required' => true,
                'multiple' => true,
                'choices' => array(
                    'kilometer' => 'km',
                    'mile' => 'ml',
                ),
                'choices_as_values' => true,
            ))
        ;
    }

}
