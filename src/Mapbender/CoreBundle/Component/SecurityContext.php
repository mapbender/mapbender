<?php
namespace Mapbender\CoreBundle\Component;

use FOM\UserBundle\Entity\User;
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
     * Is current user an master?
     *
     * @param $object
     * @return bool
     */
    public function isUserAnMaster($object)
    {
        return $this->isGranted(self::PERMISSION_MASTER, $object);
    }

    /**
     * Is current user an master?
     *
     * @param $object
     * @return bool
     */
    public function isUserAnOperator($object)
    {
        return $this->isGranted(self::PERMISSION_OPERATOR, $object);
    }

    /**
     * Is user allowed to create?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToCreate($object)
    {
        //$oid = new ObjectIdentity('class', get_class($object));
        return $this->isGranted(self::PERMISSION_CREATE, $object);
    }

    /**
     * Is current user an master?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToDelete($object)
    {
        return $this->isGranted(self::PERMISSION_DELETE, $object);
    }

    /**
     * Is current user an master?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToEdit($object)
    {
        return $this->isGranted(self::PERMISSION_EDIT, $object);
    }

    /**
     * Is current user an master?
     *
     * @param $object
     * @return bool
     */
    public function isUserAllowedToView($object)
    {
        return $this->isGranted(self::PERMISSION_VIEW, $object);
    }
}