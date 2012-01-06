<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class ApplicationType extends AbstractType {
    public function getName() {
        return 'application';
    }

    public function getDefaultOptions(array $options) {
        return array(
            'available_templates' => array());
    }

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
                    'title' => 'The description is used in overview lists.')))
            ->add('template', 'choice', array(
                'choices' => $options['available_templates'],
                'attr' => array(
                    'title' => 'The HTML template used for this '
                    .'application.')));
    }
}

