<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class HttpSourceOriginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('originUrl', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => true,
                'label' => 'mb.manager.source.serviceurl',
                'attr' => array(
                    // @todo: translate
                    'title' => 'The GetCapabilities url',
                ),
            ))
            ->add('username', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'mb.manager.source.username',
                'attr' => array(
                    'autocomplete' => 'off',
                ),
            ))
            ->add('password', 'Symfony\Component\Form\Extension\Core\Type\PasswordType', array(
                'required' => false,
                'label' => 'mb.manager.source.password',
                'attr' => array(
                    'autocomplete' => 'off',
                ),
            ))
        ;
    }
}
