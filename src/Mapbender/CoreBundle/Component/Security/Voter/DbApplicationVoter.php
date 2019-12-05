<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DbApplicationVoter extends Voter
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
        return $attribute === 'VIEW' && is_object($subject) && ($subject instanceof Application) && $subject->getSource() !== Application::SOURCE_YAML && !$subject->isPublished();
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        switch ($attribute) {
            case 'VIEW':
                return $this->voteViewUnpublished($subject, $token);
            default:
                throw new \LogicException("Unsupported grant attribute " . print_r($attribute, true));
        }
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
