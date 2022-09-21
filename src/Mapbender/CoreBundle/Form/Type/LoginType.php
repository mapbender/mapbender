<?php


namespace Mapbender\CoreBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class LoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('_username', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'label' => 'fom.user.login.login.username',
                'attr' => array(
                    'autofocus' => 'autofocus',
                ),
                'constraints' => array(
                    new Constraints\NotBlank(),
                ),
            ))
            ->add('_password', 'Symfony\Component\Form\Extension\Core\Type\PasswordType', array(
                'label' => 'fom.user.login.login.password',
            ))
        ;
    }

}
