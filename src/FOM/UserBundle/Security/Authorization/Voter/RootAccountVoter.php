<?php

namespace FOM\UserBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Root account voter.
 *
 * This voter grants access if user id is 1.
 *
 * @author Christian Wygoda
 */
class RootAccountVoter implements VoterInterface
{
    function vote(TokenInterface $token, $subject, array $attributes)
    {
        $user = $token->getUser();
        if ($user && \is_object($user) && \method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}

