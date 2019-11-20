<?php

namespace Mapbender\CoreBundle\Component\Security\Voter;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class YamlApplicationVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        // only vote for VIEW on Yaml-defined Application instances
        return $attribute === 'VIEW' && is_object($subject) && ($subject instanceof Application) && $subject->getSource() === Application::SOURCE_YAML;
    }

    protected function voteOnAttribute($attribute, $subject, \Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token)
    {
        /** @var Application $subject */
        if ($token instanceof AnonymousToken) {
            return $subject->isPublished() || in_array('IS_AUTHENTICATED_ANONYMOUSLY', $subject->getYamlRoles() ?: array());
        }
        $appRoles = $subject->getYamlRoles() ?: array();
        foreach ($token->getRoles() as $tokenRole) {
            if (in_array($tokenRole->getRole(), $appRoles)) {
                return true;
            }
        }
        return false;
    }
}
