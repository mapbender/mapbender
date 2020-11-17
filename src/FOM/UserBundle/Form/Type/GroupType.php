<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class GroupType extends AbstractType
{
    /** @var string */
    protected $userEntityClass;

    public function __construct($userEntityClass)
    {
        $this->userEntityClass = $userEntityClass;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'label' => 'Name',
            ))
            ->add('description', 'Symfony\Component\Form\Extension\Core\Type\TextareaType', array(
                'required' => false,
                'label' => 'fom.user.user.container.description',
            ))
            ->add('users', 'Symfony\Bridge\Doctrine\Form\Type\EntityType', array(
                'class' =>  $this->userEntityClass,
                'expanded' => true,
                'multiple' => true,
                'choice_label' => 'username',
                'label' => 'Users',
                // collection field rendering bypasses form theme; suppress
                // the spurious label if collection is empty
                'label_attr' => array(
                    'class' => 'hidden',
                ),
            ));
    }
}
