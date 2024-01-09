<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnlineResourceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'compound' => true,
            'label' => false,
        ));
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('format', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                    'required' => false,
                    'label' => 'mb.core.admin.onlineresource.format',
            ))
            ->add('href', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'mb.core.admin.onlineresource.href',
                'required' => false,
            ))
        ;
    }

}

