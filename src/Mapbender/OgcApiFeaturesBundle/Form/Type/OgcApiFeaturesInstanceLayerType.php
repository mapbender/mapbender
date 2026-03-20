<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;

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
                'label' => 'mb.ogcapifeatures.admin.layer.min_scale',
                'attr' => ['class' => 'form-control-sm'],
            ])
            ->add('maxScale', NumberType::class, [
                'required' => false,
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
            ->add('priority', HiddenType::class, array(
                'required' => true,
            ))
        ;
    }
}
