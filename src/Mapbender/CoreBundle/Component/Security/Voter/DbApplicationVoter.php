<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;

class DbApplicationVoter extends BaseApplicationVoter
{
    /** @var AccessDecisionManagerInterface */
    protected $accessDecisionManager;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }

    protected function supports($attribute, $subject)
    {
        // only vote on database / persistable Application instances
        // Abstain on published Application (Symfony default ACL handles all of that by itself)
        /** @var mixed|Application $subject */
        return parent::supports($attribute, $subject) && $subject->getSource() !== Application::SOURCE_YAML && !$subject->isPublished();
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        switch ($attribute) {
            case 'VIEW':
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
}
