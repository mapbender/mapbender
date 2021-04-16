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
            // VIEW: only vote on database / persistable Application instances that are published
            // Abstain on unpublished Application (access determined by ACL; getting involved in voting would cause an infinite loop)
            if ($attribute === 'VIEW') {
                return $subject->isPublished();
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
        /** @var Application $subject */
        switch ($attribute) {
            case 'VIEW':
                // Is published; see supports
                assert(!!$subject->isPublished());
                return true;
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

    protected function voteOnClone(Application $application, TokenInterface $token)
    {
        // Require edit grant for cloned application
        return parent::voteOnClone($application, $token) && $this->accessDecisionManager->decide($token, array('EDIT'), $application);
    }
}
