<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Mapbender\ManagerBundle\Form\DataTransformer\YAMLDataTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

class YAMLConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->resetViewTransformers()
            ->addViewTransformer(new YAMLDataTransformer());
    }

    public function getParent()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\TextareaType';
    }

    public function getName()
    {
        return 'yaml_configuration';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'attr' => array(
                'class' => 'code-yaml',
            ),
        ));
    }
}

