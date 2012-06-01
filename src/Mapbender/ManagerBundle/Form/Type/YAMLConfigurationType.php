<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;
use Mapbender\ManagerBundle\Form\DataTransformer\YAMLDataTransformer;

class YAMLConfigurationType extends AbstractType
{
    public function buildForm(FormBuilder $builder, array $options)
    {
        $builder->appendClientTransformer(new YAMLDataTransformer());
    }

    public function getParent(array $options)
    {
        return 'textarea';
    }

    public function getName()
    {
        return 'yaml_configuration';
    }
}

