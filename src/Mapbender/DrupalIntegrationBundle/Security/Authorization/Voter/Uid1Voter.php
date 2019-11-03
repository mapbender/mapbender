<?php

namespace Mapbender\DrupalIntegrationBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * UID 1 voter.
 *
 * This voter grants access if Drupal user uid is 1.
 *
 * @author Christian Wygoda
 */
class Uid1Voter implements VoterInterface
{
    public function supportsAttribute($attribute)
    {
        return true;
    }

    public function supportsClass($class)
    {
        return true;
    }

    function vote(TokenInterface $token, $object, array $attributes)
    {
        $user = $token->getUser();

        if(is_object($user) && get_class($user) === 'Mapbender\DrupalIntegrationBundle\Security\User\DrupalUser' && $user->getId() == 1) {
            return VoterInterface::ACCESS_GRANTED;
        }

        return VoterInterface::ACCESS_ABSTAIN;
    }
}
