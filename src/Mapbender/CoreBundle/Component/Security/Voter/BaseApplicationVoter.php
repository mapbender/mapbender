<?php


namespace Mapbender\CoreBundle\Component\Security\Voter;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;


abstract class BaseApplicationVoter extends Voter
{
    /** @var AccessDecisionManagerInterface */
    protected $accessDecisionManager;
    /** @var bool[] to speed up repeated oid grants checks */
    protected $oidGrantBuffer = array();
    /** @var ObjectIdentity */
    protected $oid;

    public function __construct(AccessDecisionManagerInterface $accessDecisionManager)
    {
        $this->accessDecisionManager = $accessDecisionManager;
        $this->oid = new ObjectIdentity('class', 'Mapbender\CoreBundle\Entity\Application');
    }

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
        switch ($attribute) {
            case 'CLONE':
                return $this->voteOnClone($subject, $token);
            default:
                throw new \LogicException("Unimplemented check for Application grant attribute " . print_r($attribute, true));
        }
    }

    /**
     * @param Application $subject
     * @return string[]
     */
    protected function getSupportedAttributes(Application $subject)
    {
        return array(
            'CLONE',
        );
    }

    protected function voteOnClone(Application $application, TokenInterface $token)
    {
        return $this->getOidGrant('CREATE', $token);
    }

    protected function getOidGrant($attribute, $token)
    {
        // OID grants for a combination of token and attribute can be buffered and reused within the same
        // request scope.
        // @todo: if there's an event for ACL data updates, we should listen to it and clear the buffer for safety
        $bufferKey = \spl_object_hash($token) . "{$attribute}";
        if (!\array_key_exists($bufferKey, $this->oidGrantBuffer)) {
            $this->oidGrantBuffer[$bufferKey] = $this->accessDecisionManager->decide($token, array($attribute), $this->oid);
        }

        return $this->oidGrantBuffer[$bufferKey];
    }

    /**
     * Get role names from given token INCLUDING roles assigned by special snowflake FOM.
     * @todo (in FOM): FOM-managed roles should already be on the token
     *
     * @param TokenInterface $token
     * @return string[]
     */
    protected function getRoleNamesFromToken(TokenInterface $token)
    {
        $names = array();
        foreach ($token->getRoles() as $tokenRole) {
            $names[] = $tokenRole->getRole();
        }
        $user = $token->getUser();
        if ($user && \is_object($user) && ($user instanceof \FOM\UserBundle\Entity\User)) {
            // custom FOM Group entity assignment roles are NOT visible via token->getRoles
            // @todo: fix this in FOM
            $names = array_values(array_unique(array_merge($names, $user->getRoles())));
        }
        return $names;
    }
}
