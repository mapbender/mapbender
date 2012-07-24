<?php

namespace Mapbender\ManagerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;

class UserForgotPassType extends AbstractType {
    public function getName() {
        return 'user';
    }

    public function buildForm(FormBuilderInterface $builder, array $options) {
        $builder
            ->add('username', 'text', array(
                'label' => 'Username or Email'));

    }

    // TODO: Switch to setDefaultOptions (before Symfony 2.3)
    public function getDefaultOptions() {
        return array('requireUsername' => true);
    }
}