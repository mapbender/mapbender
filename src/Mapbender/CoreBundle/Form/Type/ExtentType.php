<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class ExtentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('0', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'min x',
                'attr' => array(
                    'placeholder' => 'min x',
                ),
            ))
            ->add('1', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'min y',
                'attr' => array(
                    'placeholder' => 'min y',
                ),
            ))
            ->add('2', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'max x',
                'attr' => array(
                    'placeholder' => 'max x',
                ),
            ))
            ->add('3', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'max y',
                'attr' => array(
                    'placeholder' => 'max y',
                ),
            ))
        ;
    }
}
