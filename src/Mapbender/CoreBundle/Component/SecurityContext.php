<?php
namespace Mapbender\CoreBundle\Component;

use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Model\ObjectIdentityInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Class SecurityContext
 *
 * @package   FOM\UserBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @author    Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @copyright 2015 by WhereGroup GmbH & Co. KG
 */
class SecurityContext extends \Symfony\Component\Security\Core\SecurityContext
{
    const PERMISSION_MASTER   = "MASTER";
    const PERMISSION_OPERATOR = "OPERATOR";
    const PERMISSION_CREATE   = "CREATE";
    const PERMISSION_DELETE   = "DELETE";
    const PERMISSION_EDIT     = "EDIT";
    const PERMISSION_VIEW     = "VIEW";
    const USER_ANONYMOUS_ID   = 0;
    const USER_ANONYMOUS_NAME = "anon.";

    /**
     * Get current logged user by the token
     *
     * @return User
     */
    public function getUser()
    {
        /** @var User $user */
        $user = $this->getToken()->getUser();
        if (!$this->isUserLoggedIn()) {
            $user = new User();
            $user->setUsername(static::USER_ANONYMOUS_NAME);
        }
        return $user;
    }

    /**
     * Get current user role list
     *
     * @return array Role name list
     */
    public function getRolesAsArray()
    {
        $userRoles = $this->getToken()->getRoles();
        $temp      = array();
        foreach ($userRoles as $role) {
            $temp[] = $role->getRole();
        }
        return $temp;
    }

    /**
     * Is user logged in?
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        $user = $this->getToken()->getUser();
        return is_object($user);
    }

    /**
     * Checks the grant for an action and an object.
     *
     * @param string $action         action "CREATE"
     * @param object $object         the object
     * @param bool   $throwException Throw exception if current user isn't allowed to do that
     * @return bool
     */
    public function checkGranted($action, $object, $throwException = true)
    {
        $permissionGranted = true;
        switch ($action) {
            case self::PERMISSION_MASTER:
                $permissionGranted = $this->isUserAnMaster($object);
                break;
            case self::PERMISSION_OPERATOR:
                $permissionGranted = $this->isUserAnOperator($object);
                break;
            case self::PERMISSION_CREATE:
                $permissionGranted = $this->isUserAllowedToCreate($object);
                break;
            case self::PERMISSION_VIEW:
                $permissionGranted = $this->isUserAllowedToView($object);
                break;
            case self::PERMISSION_EDIT:
                $permissionGranted = $this->isUserAllowedToEdit($object);
                break;
            case self::PERMISSION_DELETE:
                $permissionGranted = $this->isUserAllowedToDelete($object);
                break;
        }

        if (!$permissionGranted && $throwException) {
            throw new AccessDeniedException();
        }
        return $permissionGranted;
    }

    /**
     * Is current user an object master?
     *
     * @param $object
     * @return bool
     */
    public function isUserAnMaster($object)
    {
        return $this->isGranted(self::PERMISSION_MASTER, $object);
    }

    /**
     * Is current user an object operator?
     *
     * @param $object
     * @return bool
     */
    public function isUserAnOperator($object)
    {
        return $this->isGranted(self::PERMISSION_OPERATOR, $object);
    }

    /**
     * Is current user allowed to create object?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToCreate($object)
    {
        $identity = $this->getClassIdentity($object);
        return $this->isGranted(self::PERMISSION_CREATE, $identity);
    }

    /**
     * Is current user allowed to delete object?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToDelete($object)
    {
        return $this->isGranted(self::PERMISSION_DELETE, $object);
    }

    /**
     * Is current user allowed to edit object?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToEdit($object)
    {
        return $this->isGranted(self::PERMISSION_EDIT, $object);
    }

    /**
     * Is current user allowed to view object?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToView($object)
    {
        return $this->isGranted(self::PERMISSION_VIEW, $object);
    }

    /**
     * Normalize passed string or class instance into an ObjectIdentity describing the class.
     * This is useful for grants operating on types, not concrete instances (i.e. create actions).
     * If argument is already an ObjectIdentity[Interface], it will be returned unchanged.
     *
     * @param object|string $objectOrClassName instance or (qualfied) class name as string
     * @return ObjectIdentityInterface
     */
    public static function getClassIdentity($objectOrClassName)
    {
        if (is_string($objectOrClassName)) {
            // assume we have a class name
            return new ObjectIdentity('class', $objectOrClassName);
        } elseif ($objectOrClassName instanceof ObjectIdentityInterface) {
            // already an object identity, return as is
            return $objectOrClassName;
        } else {
            if (!is_object($objectOrClassName)) {
                throw new \InvalidArgumentException("Unsupported argument type " . gettype($objectOrClassName));
            }
            return new ObjectIdentity('class', get_class($objectOrClassName));
        }
    }
}
