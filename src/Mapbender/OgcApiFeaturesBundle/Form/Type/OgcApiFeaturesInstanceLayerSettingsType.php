<?php

namespace Mapbender\OgcApiFeaturesBundle\Form\Type;

use Mapbender\OgcApiFeaturesBundle\Entity\OgcApiFeaturesInstanceLayer;
use Mapbender\OgcApiFeaturesBundle\Form\Transformer\JsonArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OgcApiFeaturesInstanceLayerSettingsType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OgcApiFeaturesInstanceLayer::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('tooltipPropertyMap', HiddenType::class, [
            'required' => false,
        ]);

        $builder->get('tooltipPropertyMap')->addModelTransformer(new JsonArrayTransformer());
    }
}
