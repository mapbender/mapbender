<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OgcApiFeaturesInstanceLayerType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OgcApiFeaturesInstanceLayer::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.layer.title',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('active', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('minScale', NumberType::class, [
                'required' => false,
                'html5' => true,
                'label' => 'mb.ogcapifeatures.admin.layer.min_scale',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('maxScale', NumberType::class, [
                'required' => false,
                'html5' => true,
                'label' => 'mb.ogcapifeatures.admin.layer.max_scale',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('featureLimit', IntegerType::class, [
                'required' => false,
                'label' => 'mb.ogcapifeatures.admin.feature_limit',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('allowSelected', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('selected', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('allowInfo', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('info', CheckboxType::class, [
                'required' => false,
                'label' => false,
            ])
            ->add('priority', HiddenType::class, [
                'required' => true,
            ])
            ->add('styleId', ChoiceType::class, [
                'required' => false,
                'label' => false,
                'placeholder' => 'mb.manager.admin.style.none',
                'attr' => ['class' => 'form-select form-select-sm style-select'],
            ])
            ->add('secondaryStyleIds', ChoiceType::class, [
                'required' => false,
                'multiple' => true,
                'label' => false,
                'attr' => ['class' => 'form-select form-select-sm secondary-style-select', 'size' => 6],
            ]);
    }
}
