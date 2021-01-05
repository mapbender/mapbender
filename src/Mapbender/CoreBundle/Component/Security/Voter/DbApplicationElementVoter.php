<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;


use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Exception\AclNotFoundException;
use Symfony\Component\Security\Acl\Exception\NoAceFoundException;
use Symfony\Component\Security\Acl\Exception\NotAllAclsFoundException;
use Symfony\Component\Security\Acl\Model\AclProviderInterface;
use Symfony\Component\Security\Acl\Model\SecurityIdentityRetrievalStrategyInterface;
use Symfony\Component\Security\Acl\Permission\PermissionMapInterface;
use Symfony\Component\Security\Acl\Util\ClassUtils;
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
    /** @var Acl[][] */
    protected $applicationAclBuffer = array();

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
        $aclMap = $this->getApplicationElementAcls($subject->getApplication());
        if (empty($aclMap[$subject->getId()])) {
            // If ACL on concrete Element doesn't exist, grant to all (this is unlike AclVoter which would deny)
            return true;
        }
        $acl = $aclMap[$subject->getId()];
        // If ACL exists but is empty, grant to all (this is unlike AclVoter which would deny)
        if (!$acl->getObjectAces()) {
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

    /**
     * Bulk-(pre)fetch ACLs for all Elements inside the given $application.
     * This is ~10x faster than individual single-OID lookups for typical applications.
     *
     * @param Application $application
     * @return Acl[]
     */
    protected function getApplicationElementAcls(Application $application)
    {
        $key = \spl_object_hash($application);
        if (!array_key_exists($key, $this->applicationAclBuffer)) {
            $prefetchOids = array();
            $idClass = null;
            foreach ($application->getElements() as $element) {
                $idClass = $idClass ?: ClassUtils::getRealClass($element);
                $prefetchOids[] = new ObjectIdentity((string)$element->getId(), $idClass);
            }
            try {
                $oidAclMap = $this->aclProvider->findAcls($prefetchOids);
            } catch (NotAllAclsFoundException $e) {
                $oidAclMap = $e->getPartialResult();
            } catch (AclNotFoundException $e) {
                $oidAclMap = false;
            }
            $aclMap = array();
            if ($oidAclMap) {
                // Unravel returned SplObjectStorage into a mapping of element id => Acl
                foreach ($oidAclMap as $oid) {
                    /** @var ObjectIdentity $oid */
                    /** @var Acl $acl */
                    $acl = $oidAclMap[$oid];
                    $aclMap[$oid->getIdentifier()] = $acl;
                }
            }
            $this->applicationAclBuffer[$key] = $aclMap;
        }
        return $this->applicationAclBuffer[$key];
    }
}
