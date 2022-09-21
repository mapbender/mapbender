<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class UserForgotPassType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('search', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'fom.user.password.form.username_email',
                'attr' => array(
                    'autofocus' => 'on',
                ),
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
            ))
        ;

    }
}
