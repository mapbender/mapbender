<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayerThemeType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'useTheme' => true,
            'opened' => true,
            'sourceVisibility' => false,
            'allSelected' => false,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('useTheme', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
            ->add('opened', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
            ->add('sourceVisibility', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
            ->add('allSelected', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
                'required' => false,
            ))
        ;
    }
}
