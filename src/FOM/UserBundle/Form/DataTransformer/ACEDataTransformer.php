<?php

namespace FOM\UserBundle\Form\DataTransformer;

use FOM\UserBundle\Component\Ldap;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Security\Acl\Domain\Entry;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

/**
 * Service registered as form.ace.model_transformer
 * If you rewire fom.identities.provider to produce different types of users / groups for ACE assignments,
 * you should also rewire this service so it can process the new types properly.
 */
class ACEDataTransformer implements DataTransformerInterface
{
    /** @var Ldap\UserProvider */
    protected $ldapUserProvider;
    /** @var string */
    protected $defaultLdapUserClass;

    /**
     * @param Ldap\UserProvider $ldapUserProvider
     * @param string $defaultLdapUserClass
     */
    public function __construct(Ldap\UserProvider $ldapUserProvider, $defaultLdapUserClass)
    {
        $this->ldapUserProvider = $ldapUserProvider;
        $this->defaultLdapUserClass = $defaultLdapUserClass;
    }

    /**
     * Transforms an single ACE to an object suitable for ACEType
     *
     * @param Entry|array $ace
     * @return array
     */
    public function transform($ace)
    {
        $sid = null;
        $mask = null;

        $permissions = array();

        if ($ace instanceof Entry) {
            $sid = $ace->getSecurityIdentity();
            $mask = $ace->getMask();
        } elseif (is_array($ace)) {
            $sid = $ace['sid'];
            $mask = $ace['mask'];
        }

        $sidString = '';
        if ($sid instanceof RoleSecurityIdentity) {
            $sidPrefix = 'r';
            $sidName = $sid->getRole();
            $sidString = sprintf('%s:%s', $sidPrefix, $sidName);
        } elseif ($sid instanceof UserSecurityIdentity) {
            $sidPrefix = 'u';
            $sidName = $sid->getUsername();
            $sidClass = $sid->getClass();
            $sidString = sprintf('%s:%s:%s', $sidPrefix, $sidName, $sidClass);
        }

        for ($i = 1; $i <= 30; $i++) {
            $key = 1 << ($i-1);
            if($mask & $key) {
                $permissions[$i] = true;
            } else {
                $permissions[$i] = false;
            }
        }

        return array(
            'sid' => $sidString,
            'permissions' => $permissions,
        );
    }

    /**
     * Transforms an ACEType result into an ACE
     *
     * @param object $data
     * @return array
     */
    public function reverseTransform($data)
    {
        $sidParts = explode(':', $data['sid']);
        if(strtoupper($sidParts[0]) == 'R') {
            /* is rolebased */
            $sid = new RoleSecurityIdentity($sidParts[1]);
        } else {
            if(3 == count($sidParts)) {
                /* has 3 sidParts */
                $class = $sidParts[2];
            } else {
                if($this->isLdapUser($sidParts[1])) {
                    $class = $this->defaultLdapUserClass;
                } else {
                    /* is not a LDAP user*/
                    $class = 'FOM\UserBundle\Entity\User';
                }
            }
            $sid = new UserSecurityIdentity($sidParts[1], $class);
        }

        $maskBuilder = new MaskBuilder();
        foreach($data['permissions'] as $bit => $permission) {
            if(true === $permission) {
                $maskBuilder->add(1 << ($bit - 1));
            }
        }

        return array(
            'sid' => $sid,
            'mask' => $maskBuilder->get(),
        );
    }

    /**
     * @param $username
     * @return bool
     */
    public function isLdapUser($username)
    {
        return $this->ldapUserProvider->userExists($username);
    }
}
