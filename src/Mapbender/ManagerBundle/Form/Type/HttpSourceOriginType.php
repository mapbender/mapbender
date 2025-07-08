<?php


namespace Mapbender\ManagerBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HttpSourceOriginType extends SourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('originUrl', TextType::class, array(
                'required' => true,
                'label' => 'mb.manager.source.serviceurl',
                'attr' => array(
                    // @todo: translate
                    'title' => 'The GetCapabilities url',
                ),
            ))
            ->add('username', TextType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.username',
                'attr' => array(
                    'autocomplete' => 'off',
                ),
            ))
            ->add('password', PasswordType::class, array(
                'required' => false,
                'label' => 'mb.manager.source.password',
                'attr' => array(
                    'autocomplete' => 'new-password',
                ),
            ))
        ;
        if ($options['is_refresh']) {
            $builder
                ->add('activate_new_layers', CheckboxType::class, array(
                    'required' => false,
                    'label' => 'mb.manager.source.activate_new_layers',
                ))
                ->add('select_new_layers', CheckboxType::class, array(
                    'required' => false,
                    'label' => 'mb.manager.source.select_new_layers',
                ))
            ;
        }
    }
}
