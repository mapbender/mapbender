<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @author apour
 * @author Christian Wygoda
 */
class UserRegistrationType extends AbstractType
{

    public function getParent()
    {
        return 'FOM\UserBundle\Form\Type\UserPasswordMixinType';
    }

    public function buildForm(FormBuilderInterface $builder,array $options)
    {
        $builder->add("username", 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
            'required' => true,
            'label' => 'fom.user.user.container.username',
            'attr' => array(
                'autofocus' => 'on',
            ),
        ));

        $builder->add("email", 'Symfony\Component\Form\Extension\Core\Type\EmailType', array(
            'required' => true,
            'label' => 'fom.user.registration.form.email',
        ));
    }
}
