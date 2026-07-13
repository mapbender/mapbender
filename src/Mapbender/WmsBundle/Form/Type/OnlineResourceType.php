<?php

namespace Mapbender\WmsBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OnlineResourceType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'compound' => true,
            'label' => false,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('format', TextType::class, [
                    'required' => false,
                    'label' => 'mb.core.admin.onlineresource.format',
            ])
            ->add('href', TextType::class, [
                'label' => 'mb.core.admin.onlineresource.href',
                'required' => false,
            ])
        ;
    }

}

