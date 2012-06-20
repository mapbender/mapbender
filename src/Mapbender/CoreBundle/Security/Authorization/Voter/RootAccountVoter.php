<?php

namespace Mapbender\CoreBundle\Security\Authorization\Voter;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Root account voter.
 *
 * This voter grants access if user id is 1.
 *
 * @author Christian Wygoda
 */
class RootAccountVoter implements VoterInterface {
    public function __construct(ContainerInterface $container) {
        $this->container     = $container;
    }

    public function supportsAttribute($attribute) {
        return true;
    }

    public function supportsClass($class) {
        return true;
    }

    function vote(TokenInterface $token, $object, array $attributes) {
        $user = $token->getUser();

        if(is_object($user)
            && get_class($user) === 'Mapbender\CoreBundle\Entity\User'
            && $user->getId() === 1) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}

