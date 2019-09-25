<?php

namespace Mapbender\CoreBundle\Element\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayerThemeType extends AbstractType
{

    public function getName()
    {
        return 'theme';
    }

    /**
     * @inheritdoc
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'id' => null,
            'title' => '',
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
            ->add('id', 'hidden', array(
                'required' => true,
            ))
            ->add('title', 'hidden', array(
                'required' => false,
            ))
            ->add('useTheme', 'checkbox', array(
                'required' => false,
            ))
            ->add('opened', 'checkbox', array(
                'required' => false,
            ))
            ->add('sourceVisibility', 'checkbox', array(
                'required' => false,
            ))
            ->add('allSelected', 'checkbox', array(
                'required' => false,
            ))
        ;
    }
}
