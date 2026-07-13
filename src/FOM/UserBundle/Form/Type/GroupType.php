<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GroupType extends AbstractType
{
    /**
     * @param string $userEntityClass
     */
    public function __construct(protected $userEntityClass)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Name',
            ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'fom.user.user.container.description',
            ])
            ->add('users', EntityType::class, [
                'class' =>  $this->userEntityClass,
                'expanded' => true,
                'multiple' => true,
                'choice_label' => 'username',
                'label' => 'Users',
                // collection field rendering bypasses form theme; suppress
                // the spurious label if collection is empty
                'label_attr' => [
                    'class' => 'hidden',
                ],
            ]);
    }
}
