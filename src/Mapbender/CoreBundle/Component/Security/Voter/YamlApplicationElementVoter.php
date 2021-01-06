<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;


use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class YamlApplicationElementVoter extends Voter
{
    /** @var AccessDecisionManagerInterface */
    protected $accessDecisionManager;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }

    protected function supports($attribute, $subject)
    {
        // Only vote on VIEW on an Element in a Yaml-defined Application
        return
            ($attribute === 'VIEW')
            && is_object($subject) && ($subject instanceof Element)
            && ($subject->getApplication()) && ($subject->getApplication()->isYamlBased())
        ;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Element $subject */
        $roleNames = $subject->getYamlRoles();
        if (!$roleNames) {
            // Empty list of roles => allow all
            return true;
        }
        foreach ($roleNames as $roleName) {
            if ($this->accessDecisionManager->decide($token, array($roleName), null)) {
                return true;
            }
        }
        return false;
    }
}
