<?php

namespace Mapbender\XyzBundle\Type;

use Mapbender\ManagerBundle\Form\Type\SourceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class XyzSourceType extends SourceType
{

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('urlTemplate', TextType::class, [
                'label' => 'mb.xyz.admin.url_template',
                'attr' => [
                    'placeholder' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                ],
            ])
            ->add('title', TextType::class, [
                'label' => 'mb.xyz.admin.title',
                'required' => false,
            ])
        ;
    }

}
