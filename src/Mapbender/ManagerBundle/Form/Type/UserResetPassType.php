<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;

class UserResetPassType extends AbstractType {
    public function getName() {
        return 'user';
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('id', 'hidden', array())
            ->add('password', 'repeated', array(
                'type' => 'password',
                'invalid_message' => 'The password fields must match.',
                'options' => array(
                    'required' => $options['requirePassword'],
                    'label' => 'Password')));

    }

    // TODO: Switch to setDefaultOptions (before Symfony 2.3)
    public function getDefaultOptions() {
        return array('requirePassword' => true);
    }
}

