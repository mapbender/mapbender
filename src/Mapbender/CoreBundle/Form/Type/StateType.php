<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StateType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'label_attr' => array(
                'class' => 'hidden',
            ),
            'compound' => true,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('id', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', array(
                'required' => false,
            ))
            ->add('slug', 'Symfony\Component\Form\Extension\Core\Type\HiddenType')
            ->add('json', 'Symfony\Component\Form\Extension\Core\Type\HiddenType')
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType')
        ;
    }
}

