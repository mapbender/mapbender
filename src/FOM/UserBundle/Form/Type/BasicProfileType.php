<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use FOM\UserBundle\Entity\BasicProfile;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BasicProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $roles = BasicProfile::getOrganizationRoleChoices();

        $builder
            ->add('firstName', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.firstname',
            ])
            ->add('lastName', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.lastName',
            ])
            ->add('notes', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.notes',
            ])
            ->add('phone', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.phone',
            ])
            ->add('street', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.street',
            ])
            ->add('zipCode', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.zipCode',
            ])
            ->add('city', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.city',
            ])
            ->add('country', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.country',
            ])
            ->add('organizationName', TextType::class, [
                'required' => false,
                'label' => 'form.profile.basic.organizationName',
            ])
            ->add('organizationRole', ChoiceType::class, [
                'choices' => array_flip($roles),
                'placeholder' => 'mb.form.choice_optional',
                'required' => false,
                'label' => 'form.profile.basic.organizationRole',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BasicProfile::class,
        ]);
    }
}
