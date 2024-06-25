<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class UserResetPassType extends AbstractType
{
    public function getParent(): string
    {
        return 'FOM\UserBundle\Form\Type\UserPasswordMixinType';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('requirePassword', true);
    }
}
