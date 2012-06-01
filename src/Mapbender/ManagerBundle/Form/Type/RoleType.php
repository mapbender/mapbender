<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilder;

class RoleType extends AbstractType {
    public function getName() {
        return 'role';
    }

    public function buildForm(FormBuilder $builder, array $options) {
        $builder
            ->add('title', 'text', array(
                'label' => 'Role title'))
            ->add('description', 'textarea', array(
                'label' => 'Role description'))
            ->add('users', 'entity', array(
                'class' =>  'MapbenderCoreBundle:User',
                'expanded' => true,
                'multiple' => true,
                'property' => 'username',
                'label' => 'Users'));

    }

    public function getDefaultOptions(array $options) {
        return array(
            'exclude_fau_role' => false);
    }
}

