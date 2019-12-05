<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;


abstract class BaseApplicationVoter extends Voter
{

    /**
     * Checks for basic voting precondition ($subject is an Application instance). Child classes should perform any
     * additional checks.
     *
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports($attribute, $subject)
    {
        return is_object($subject) && ($subject instanceof Application) && \in_array($attribute, $this->getSupportedAttributes($subject));
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        throw new \LogicException("Unimplemented check for Application grant attribute " . print_r($attribute, true));
    }

    /**
     * @param Application $subject
     * @return string[]
     */
    protected function getSupportedAttributes(Application $subject)
    {
        return array();
    }
}
