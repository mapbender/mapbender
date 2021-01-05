<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class DbApplicationVoter extends BaseApplicationVoter
{
    protected function supports($attribute, $subject)
    {
        /** @var mixed|Application $subject */
        if (parent::supports($attribute, $subject) && $subject->getSource() !== Application::SOURCE_YAML) {
            // VIEW: only vote on database / persistable Application instances
            // Abstain on published Application (Symfony default ACL handles all of that by itself)
            if ($attribute === 'VIEW') {
                return !$subject->isPublished();
            } else {
                // rely on parent support for 'CLONE'
                return true;
            }
        } else {
            return false;
        }
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        switch ($attribute) {
            case 'VIEW':
                // no own logic for published Application (see supports)
                return $this->voteViewUnpublished($subject, $token);
            default:
                return parent::voteOnAttribute($attribute, $subject, $token);
        }
    }

    protected function getSupportedAttributes(Application $subject)
    {
        return array_unique(array_merge(parent::getSupportedAttributes($subject), array(
            'VIEW',
        )));
    }

    /**
     * Decide on view grant.
     *
     * @param Application $subject guaranteed to be Db-based (see supports)
     * @param TokenInterface $token
     * @return bool true for grant, false for deny (cannot abstain here)
     */
    protected function voteViewUnpublished(Application $subject, TokenInterface $token)
    {
        // forward to ACL check on 'EDIT' attribute and explicitly DENY if not granted
        return $this->accessDecisionManager->decide($token, array('EDIT'), $subject);
    }

    protected function voteOnClone(Application $application, TokenInterface $token)
    {
        // Require edit grant for cloned application
        return parent::voteOnClone($application, $token) && $this->accessDecisionManager->decide($token, array('EDIT'), $application);
    }
}
