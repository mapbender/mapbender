<?php


namespace FOM\UserBundle\Form\Type;


use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use FOM\UserBundle\Component\UserHelperService;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserPasswordMixinType extends AbstractType
{
    /**
     * @param UserHelperService $userHelperService
     */
    public function __construct(protected UserHelperService $userHelperService)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'requirePassword' => null,
        ]);
        $resolver->setAllowedTypes('requirePassword', [
            'boolean',
            'null',
        ]);
    }

    /**
     * @param FormInterface|FormBuilderInterface $form
     * @param array $options
     */
    public function addPasswordField($form, array $options): void
    {
        $constraints = $this->userHelperService->getPasswordConstraints();
        if ($options['requirePassword']) {
            $constraints[] = new NotBlank();
        }
        $form
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                // do not, ever, synchronize with password hash attribute 'password'
                'mapped' => false,
                // require password input for new users
                // password editing for existing users is optional
                'required' => $options['requirePassword'],
                'invalid_message' => 'fom.user.password.repeat_mismatch',
                'first_options' => [
                    'label' => 'fom.user.registration.form.choose_password',
                ],
                'second_options' => [
                    'label' => 'fom.user.registration.form.confirm_password',
                ],
                'options' => ['attr' => ['autocomplete' => 'new-password']],
                'constraints' => $constraints,
            ])
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // NOTE: PHP < 7.1 disallows use ($this) in lambdas
        $type = $this;
        if ($options['requirePassword'] !== null) {
            $this->addPasswordField($builder, $options);
        } else {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, fn(FormEvent $event) => $type->preSetData($event));
        }
        $builder->addEventListener(FormEvents::POST_SUBMIT, fn(FormEvent $event) => $type->postSubmit($event));
    }

    public function postSubmit(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var User $user */
        $user = $form->getData();
        $passwordField = $form->get('password');
        $passwordPlain = $passwordField->getNormData();
        // NOTE: required fields with empty data are never valid
        if ($passwordField->isValid() && $passwordPlain) {
            $this->userHelperService->setPassword($user, $passwordPlain);
        }
    }

    public function preSetData(FormEvent $event): void
    {
        /** @var User|null $user */
        $user = $event->getData();
        $options = [
            'requirePassword' => (!$user || !$user->getId()),
        ];
        $this->addPasswordField($event->getForm(), $options);
    }
}
