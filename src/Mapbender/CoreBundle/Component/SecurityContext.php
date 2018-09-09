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
    /** @deprecated this is not a permission, it's a role; remove in 3.0.8 */
    const PERMISSION_MASTER   = "MASTER";
    /** @deprecated this is not a permission, it's a role; remove in 3.0.8 */
    const PERMISSION_OPERATOR = "OPERATOR";
    const PERMISSION_CREATE   = "CREATE";
    const PERMISSION_DELETE   = "DELETE";
    const PERMISSION_EDIT     = "EDIT";
    const PERMISSION_VIEW     = "VIEW";
    /** @deprecated remove in 3.0.8 */
    const USER_ANONYMOUS_ID   = 0;
    /** @deprecated remove in 3.0.8 */
    const USER_ANONYMOUS_NAME = "anon.";

    /**
     * Get current logged user by the token
     *
     * Invents a magic User object with id 0 and name "anon." if there is no User object.
     *
     * @return User
     * @deprecated call getToken on your own; this will prevent problems dealing with the "anon." user; remove in 3.0.8
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
     * Wraps isGranted in an ObjectIdentity creation for "CREATE" action plus an optional (default enabled)
     * http execption throw if not granted.
     *
     * @param string $action         action "CREATE"
     * @param object $object for all grants checks except "CREATE", where we check on a class-type ObjectIdentity
     * @param bool   $throwException Throw exception if current user isn't allowed to do that
     * @return bool
     * @deprecated access security.authorization_checker::isGranted directly; remove in 3.0.8
     * @deprecated throw appropriate domain-specific exceptions; this method throws HTTP exceptions for Controllers
     * @deprecated make your own appropriate decisions about instance checks vs class checks
     */
    public function checkGranted($action, $object, $throwException = true)
    {
        switch ($action) {
            case self::PERMISSION_CREATE:
                $oid = $this->getClassIdentity($object);
                $permissionGranted = $this->isGranted($action, $oid);
                break;
            default:
                $permissionGranted = $this->isGranted($action, $object);
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
     * @deprecated not a permission; remove in 3.0.8
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
     * @deprecated not a permission; remove in 3.0.8
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
        return $this->checkGranted(self::PERMISSION_CREATE, $object, false);
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
