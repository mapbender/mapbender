<?php

namespace Mapbender\CoreBundle\Component\Security\Voter;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class YamlApplicationVoter extends Voter
{
    protected function supports($attribute, $subject)
    {
        // only vote for VIEW on Yaml-defined Application instances
        return $attribute === 'VIEW' && is_object($subject) && ($subject instanceof Application) && $subject->getSource() === Application::SOURCE_YAML;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        switch ($attribute) {
            case 'VIEW':
                return $this->voteView($subject, $token);
            default:
                throw new \LogicException("Unsupported grant attribute " . print_r($attribute, true));
        }
    }

    /**
     * Decide on view grant.
     *
     * @param Application $subject guaranteed to be Yaml-based (see supports)
     * @param TokenInterface $token
     * @return bool true for grant, false for deny (cannot abstain here)
     */
    protected function voteView(Application $subject, TokenInterface $token)
    {
        if ($token instanceof AnonymousToken) {
            return $subject->isPublished() || in_array('IS_AUTHENTICATED_ANONYMOUSLY', $subject->getYamlRoles() ?: array());
        }
        $appRoles = $this->getApplicationRoles($subject);
        foreach ($token->getRoles() as $tokenRole) {
            if (in_array($tokenRole->getRole(), $appRoles)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Should return role identifier strings for given $application.
     * Override this for completely special sauce VIEW-grant logic
     *
     * @param Application $application guaranteed to be Yaml-based (see supports)
     * @return string[]
     */
    protected function getApplicationRoles(Application $application)
    {
        // @todo: get this (unpersistable) information out of the entity, into a separate container parameter map
        return $application->getYamlRoles() ?: array();
    }
}
