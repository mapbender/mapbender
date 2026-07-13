<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UserForgotPassType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('search', TextType::class, [
                'label' => 'fom.user.password.form.username_email',
                'attr' => [
                    'autofocus' => 'on',
                ],
                'constraints' => [
                    new NotBlank(),
                ],
            ])
        ;

    }
}
