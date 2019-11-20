<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;


use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UnpublishedApplicationVoter extends Voter
{
    /** @var AccessDecisionManagerInterface */
    protected $accessDecisionManager;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
    }

    protected function supports($attribute, $subject)
    {
        // only vote for VIEW on Application instances with published = false
        return $attribute === 'VIEW' && is_object($subject) && ($subject instanceof Application) && !$subject->isPublished();
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        /** @var Application $subject */
        // forward to ACL check on 'EDIT' attribute and explicitly DENY if not granted
        if ($subject->getSource() !== Application::SOURCE_DB) {
            // Yaml applications have no ACLs. Need to perform grants check based on class-type OID
            $aclTarget = ObjectIdentity::fromDomainObject($subject);
        } else {
            $aclTarget = $subject;
        }
        return $this->accessDecisionManager->decide($token, array('EDIT'), $aclTarget);
    }
}
