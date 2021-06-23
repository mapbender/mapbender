<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use FOM\UserBundle\Entity\BasicProfile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BasicProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $roles = BasicProfile::getOrganizationRoleChoices();

        $builder
            ->add('firstName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.firstname',
            ))
            ->add('lastName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.lastName',
            ))
            ->add('notes', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.notes',
            ))
            ->add('phone', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.phone',
            ))
            ->add('street', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.street',
            ))
            ->add('zipCode', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.zipCode',
            ))
            ->add('city', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.city',
            ))
            ->add('country', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.country',
            ))
            ->add('organizationName', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'required' => false,
                'label' => 'form.profile.basic.organizationName',
            ))
            ->add('organizationRole', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => array_flip($roles),
                'placeholder' => 'mb.form.choice_optional',
                'required' => false,
                'label' => 'form.profile.basic.organizationRole',
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'FOM\UserBundle\Entity\BasicProfile',
        ));
    }
}
