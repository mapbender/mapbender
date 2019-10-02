<?php


namespace Mapbender\CoreBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
            ))
            ->add('_password', 'Symfony\Component\Form\Extension\Core\Type\PasswordType', array(
                'label' => 'fom.user.login.login.password',
            ))
        ;
    }

    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        parent::finishView($view, $form, $options);
        $view['_username']->vars = array_replace($view['_username']->vars, array(
            'full_name' => '_username',
        ));
        $view['_password']->vars = array_replace($view['_password']->vars, array(
            'full_name' => '_password',
        ));
    }


}
