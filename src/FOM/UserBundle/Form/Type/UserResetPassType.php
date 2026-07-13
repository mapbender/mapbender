<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;


class UserResetPassType extends AbstractType
{
    public function getParent(): string
    {
        return UserPasswordMixinType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('requirePassword', true);
    }
}
