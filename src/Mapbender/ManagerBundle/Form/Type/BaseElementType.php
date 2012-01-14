<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class BaseElementType extends AbstractType {
    public function getName() {
        return 'element';
    }

    public function buildForm(FormBuilder $builder, array $options) {
        $builder->add('title', 'text', array(
                'attr' => array(
                    'title' => 'The element title, may be used in various '
                        .'places.')))
            ->add('class', 'hidden')
            ->add('configuration', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'class' => 'code code-yaml',
                    'title' => 'The element configuration. Use YAML.')));
    }
}

