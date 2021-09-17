<?php

namespace FOM\UserBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Dynamically adds the 'activated' checkbox if the currently editING user is != the editED user,
 * to prevent account self-destruction.
 *
 * If a user is saved as activated, also clears the registration token. This allows administrators
 * to manually finish / fix the registration process for a pending user who may have missed the
 * token validity window.
 */
class UserSubscriber implements EventSubscriberInterface
{
    /** @var TokenStorageInterface */
    protected $tokenStorage;

    /**
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::SUBMIT => 'submit',
        );
    }

    /**
     * @param FormEvent $event
     */
    public function submit(FormEvent $event)
    {
        /** @var User|null $user */
        $user = $event->getData();
        if (null === $user) {
            return;
        }
        if (!$event->getForm()->get('activated')->isDisabled()) {
            $activated = $event->getForm()->get('activated')->getData();
            if ($activated) {
                $user->setRegistrationToken(null);
            } elseif (!$user->getRegistrationToken()) {
                $user->setRegistrationToken(hash("sha1",rand()));
            }
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        /** @var User|null $user */
        $user = $event->getData();
        if (null === $user) {
            return;
        }

        $currentUser = $this->getCurrentUser();
        $event->getForm()->add('activated', 'Symfony\Component\Form\Extension\Core\Type\CheckboxType', array(
            'data' => $user->getRegistrationToken() ? false : true,
            'label' => 'fom.user.user.container.activated',
            'required' => false,
            'mapped' => false,
            'disabled' => ($currentUser && $currentUser === $user),
        ));
    }

    /**
     * Retrieves current user ONLY IF its a manageable FOM\UserBundle\Enity\User instance, otherwise null.
     * @return User|null
     */
    protected function getCurrentUser()
    {
        $token = $this->tokenStorage->getToken();
        if (!($token instanceof AnonymousToken)) {
            $user = $token->getUser();
            if (is_object($user) && ($user instanceof User)) {
                return $user;
            }
        }
        return null;
    }
}
