<?php
namespace Mapbender\CoreBundle\Entity;

use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * Class AclEntry
 *
 * @author  Andriy Oblivantsev <eslider@gmail.com>
 */
class AclEntry
{
    const ROLE_TYPE = "Role";
    const USER_TYPE = "User";

    protected $identity;
    protected $type;
    protected $name;
    protected $class;

    /**
     * AclEntry constructor.
     *
     * @param SecurityIdentityInterface $identity
     */
    public function __construct(SecurityIdentityInterface $identity)
    {
        if ($identity instanceof RoleSecurityIdentity) {
            /** @var RoleSecurityIdentity $identity */
            $this->name = $identity->getRole();
            $this->type = AclEntry::ROLE_TYPE;

        } elseif ($identity instanceof UserSecurityIdentity) {
            /** @var UserSecurityIdentity $identity */
            $this->name  = $identity->getUsername();
            $this->type  = AclEntry::USER_TYPE;
            $this->class = $identity->getClass();
        }

        $this->identity = $identity;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Is type an user?
     *
     * @return bool
     */
    public function isTypeAnUser()
    {
        return $this->getType() == self::ROLE_TYPE;
    }

    /**
     * Is type an role?
     *
     * @return bool
     */
    public function isTypeAnRole()
    {
        return $this->getType() == self::USER_TYPE;
    }

    /**
     * @return RoleSecurityIdentity|UserSecurityIdentity|SecurityIdentityInterface
     */
    public function getIdentity()
    {
        return $this->identity;
    }
}