<?php

namespace Mapbender\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;

class LayersetType extends AbstractType
{

    /**
     * @inheritdoc
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {

        $builder
            ->add('title', TextType::class, [
                'attr' => [
                    'maxlength' => 128,
                    'label' => 'mb.core.admin.layerset.label.title',
                ],
            ])
            ->add('selected', CheckboxType::class, [
                'required' => false,
                'label' => 'mb.core.admin.layerset.label.selected',
            ])
        ;
    }
}

