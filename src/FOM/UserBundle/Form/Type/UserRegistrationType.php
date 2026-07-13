<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author apour
 * @author Christian Wygoda
 */
class UserRegistrationType extends AbstractType
{

    public function getParent(): string
    {
        return UserPasswordMixinType::class;
    }

    public function buildForm(FormBuilderInterface $builder,array $options): void
    {
        $builder->add("username", TextType::class, [
            'required' => true,
            'label' => 'fom.user.user.container.username',
            'attr' => [
                'autofocus' => 'on',
            ],
        ]);

        $builder->add("email", EmailType::class, [
            'required' => true,
            'label' => 'fom.user.registration.form.email',
        ]);
    }
}
