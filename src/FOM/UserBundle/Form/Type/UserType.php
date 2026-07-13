<?php

namespace FOM\UserBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Doctrine\ORM\QueryBuilder;
use FOM\UserBundle\Entity\Group;
use FOM\UserBundle\Form\EventListener\UserSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class UserType extends AbstractType
{
    /**
     * @param TokenStorageInterface $tokenStorage
     * @param string|null $profileType
     */
    public function __construct(protected TokenStorageInterface $tokenStorage, protected $profileType)
    {
    }

    public function getParent(): string
    {
        return UserPasswordMixinType::class;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber(new UserSubscriber($this->tokenStorage));
        $builder
            ->add('username', TextType::class, [
                'label' => 'fom.user.user.container.username',
                'attr' => [
                    'autofocus' => true,
                    'autocomplete' => 'off',
                ],
                'disabled' => !$options['allow_name_editing'],
                'required' => $options['allow_name_editing'],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-Mail',
            ])
        ;

        if (true === $options['group_permission']) {
            $builder
                ->add('groups', EntityType::class, [
                    'class' =>  Group::class,
                    'query_builder' => function (EntityRepository $er): QueryBuilder {
                        $qb = $er->createQueryBuilder('r')
                            ->add('orderBy', 'r.title ASC');
                        return $qb;
                    },
                    'expanded' => true,
                    'multiple' => true,
                    'choice_label' => 'title',
                    // collection field rendering bypasses form theme; suppress
                    // the spurious label if collection is empty
                    'label_attr' => [
                        'class' => 'hidden',
                    ],
                    'label' => 'fom.user.user.container.groups',
                ]);
        }

        if ($this->profileType) {
            $builder->add('profile', $this->profileType, [
                'label' => 'fom.user.user.container.profile',
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'group_permission' => false,
            'allow_name_editing' => function (Options $options): bool {
                if ($options['group_permission']) {
                    return true;
                }
                $user = $options['data'] ?? null;
                return !($user && $user->getId());
            }
        ]);
    }
}
