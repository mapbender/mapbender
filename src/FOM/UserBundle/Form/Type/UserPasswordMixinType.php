<?php


namespace FOM\UserBundle\Form\Type;


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
    /** @var UserHelperService */
    protected $userHelperService;

    /**
     * @param UserHelperService $userHelperService
     */
    public function __construct(UserHelperService $userHelperService)
    {
        $this->userHelperService = $userHelperService;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'FOM\UserBundle\Entity\User',
            'requirePassword' => null,
        ));
        $resolver->setAllowedTypes('requirePassword', array(
            'boolean',
            'null',
        ));
    }

    /**
     * @param FormInterface|FormBuilderInterface $form
     * @param array $options
     */
    public function addPasswordField($form, array $options)
    {
        $constraints = $this->userHelperService->getPasswordConstraints();
        if ($options['requirePassword']) {
            $constraints[] = new NotBlank();
        }
        $form
            ->add('password', 'Symfony\Component\Form\Extension\Core\Type\RepeatedType', array(
                'type' => 'Symfony\Component\Form\Extension\Core\Type\PasswordType',
                // do not, ever, synchronize with password hash attribute 'password'
                'mapped' => false,
                // require password input for new users
                // password editing for existing users is optional
                'required' => $options['requirePassword'],
                'invalid_message' => 'fom.user.password.repeat_mismatch',
                'first_options' => array(
                    'label' => 'fom.user.registration.form.choose_password',
                ),
                'second_options' => array(
                    'label' => 'fom.user.registration.form.confirm_password',
                ),
                'options' => ['attr' => ['autocomplete' => 'new-password']],
                'constraints' => $constraints,
            ))
        ;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // NOTE: PHP < 7.1 disallows use ($this) in lambdas
        $type = $this;
        if ($options['requirePassword'] !== null) {
            $this->addPasswordField($builder, $options);
        } else {
            $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($type) {
                return $type->preSetData($event);
            });
        }
        $builder->addEventListener(FormEvents::POST_SUBMIT, function(FormEvent $event) use ($type) {
            return $type->postSubmit($event);
        });
    }

    public function postSubmit(FormEvent $event)
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

    public function preSetData(FormEvent $event)
    {
        /** @var User|null $user */
        $user = $event->getData();
        $options = array(
            'requirePassword' => (!$user || !$user->getId()),
        );
        $this->addPasswordField($event->getForm(), $options);
    }
}
