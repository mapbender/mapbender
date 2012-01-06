<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class ElementType extends AbstractType {
    public function buildForm(FormBuilder $builder, array $options) {
        $builder->add('title', 'text', array(
                'attr' => array(
                    'title' => 'The application title, as shown in the browser '
                        . 'title bar and in lists.')))
            ->add('slug', 'text', array(
                'attr' => array(
                    'title' => 'The slug is based on the title and used in the '
                        . 'application URL.')))
            ->add('description', 'textarea', array(
                'required' => false,
                'attr' => array(
                    'title' => 'The description is used in overview lists.')));
    }

    public function getName() {
        return 'element';
    }
}

