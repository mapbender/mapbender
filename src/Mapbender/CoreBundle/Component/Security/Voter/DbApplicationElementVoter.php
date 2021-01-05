<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;


use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class DbApplicationElementVoter extends Voter
{
    /** @var AclProviderInterface */
    protected $aclProvider;
    /** @var SecurityIdentityRetrievalStrategyInterface */
    protected $sidRetrievalStrategy;
    /** @var PermissionMapInterface */
    protected $permissionMap;

    public function __construct(AclProviderInterface $aclProvider,
                                SecurityIdentityRetrievalStrategyInterface $sidRetrievalStrategy,
                                PermissionMapInterface $permissionMap)
    {
        $this->aclProvider = $aclProvider;
        $this->sidRetrievalStrategy = $sidRetrievalStrategy;
        $this->permissionMap = $permissionMap;
    }

    protected function supports($attribute, $subject)
    {
        // Only vote on VIEW on an Element in a non-Yaml-defined Application
        return
            ($attribute === 'VIEW')
            && is_object($subject) && ($subject instanceof Element)
            && ($subject->getApplication()) && (!$subject->getApplication()->isYamlBased())
        ;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        // Minimal reimplementation of AclVoter, minus FieldVote supprt, minus logging, minus ObjectIdentity renormalization
        /** @see \Symfony\Component\Security\Acl\Voter\AclVoter::vote */
        // NOTE: we cannot delegate to AuthorizationChecker via isGranted because we would cycle back here infinitely.

        /** @var Element $subject */
        $oid = ObjectIdentity::fromDomainObject($subject);
        try {
            $acl = $this->aclProvider->findAcl($oid);
            // If ACL exists but is empty, grant to all (this is unlike AclVoter which would deny)
            if (!$acl->getObjectAces()) {
                return true;
            }
        } catch (AclNotFoundException $e) {
            // If ACL doesn't exist, grant to all (this is unlike AclVoter which would deny)
            return true;
        }
        $masks = $this->permissionMap->getMasks($attribute, $subject);
        $sids = $this->sidRetrievalStrategy->getSecurityIdentities($token);
        try {
            return $acl->isGranted($masks, $sids);
        } catch (NoAceFoundException $e) {
            return false;
        }
    }
}
