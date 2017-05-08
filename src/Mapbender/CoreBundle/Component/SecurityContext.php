<?php
namespace Mapbender\CoreBundle\Component;

use FOM\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Class SecurityContext
 *
 * @package   FOM\UserBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @author    Mohamed Tahrioui <mohamed.tahrioui@wheregroup.com>
 * @copyright 2015 by WhereGroup GmbH & Co. KG
 */
class SecurityContext implements TokenStorageInterface, AuthorizationCheckerInterface
{
    const ACCESS_DENIED_ERROR  = Security::ACCESS_DENIED_ERROR;
    const AUTHENTICATION_ERROR = Security::AUTHENTICATION_ERROR;
    const LAST_USERNAME        = Security::LAST_USERNAME;
    const MAX_USERNAME_LENGTH  = Security::MAX_USERNAME_LENGTH;

    const PERMISSION_MASTER   = "MASTER";
    const PERMISSION_OPERATOR = "OPERATOR";
    const PERMISSION_CREATE   = "CREATE";
    const PERMISSION_DELETE   = "DELETE";
    const PERMISSION_EDIT     = "EDIT";
    const PERMISSION_VIEW     = "VIEW";
    const USER_ANONYMOUS_ID   = 0;
    const USER_ANONYMOUS_NAME = "anon.";


    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * For backwards compatibility, the signature of sf <2.6 still works.
     *
     * @param TokenStorageInterface|AuthenticationManagerInterface         $tokenStorage
     * @param AuthorizationCheckerInterface|AccessDecisionManagerInterface $authorizationChecker
     * @param bool                                                         $alwaysAuthenticate   only applicable with old signature
     */
    public function __construct($tokenStorage, $authorizationChecker, $alwaysAuthenticate = false)
    {
        /**
          * $securityContext = $this->get('security.authorization_checker');
        $tokenStorage = $this->get('security.token_storage');
         */
        $oldSignature = $tokenStorage instanceof AuthenticationManagerInterface && $authorizationChecker instanceof AccessDecisionManagerInterface;
        $newSignature = $tokenStorage instanceof TokenStorageInterface && $authorizationChecker instanceof AuthorizationCheckerInterface;

        // confirm possible signatures
        if (!$oldSignature && !$newSignature) {
            throw new \BadMethodCallException('Unable to construct SecurityContext, please provide the correct arguments');
        }

        if ($oldSignature) {
            // renamed for clarity
            $authenticationManager = $tokenStorage;
            $accessDecisionManager = $authorizationChecker;
            $tokenStorage = new TokenStorage();
            $authorizationChecker = new AuthorizationChecker($tokenStorage, $authenticationManager, $accessDecisionManager, $alwaysAuthenticate);
        }

        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
    }

    /**
     * @deprecated since version 2.6, to be removed in 3.0. Use TokenStorageInterface::getToken() instead.
     *
     * {@inheritdoc}
     */
    public function getToken()
    {
        return $this->tokenStorage->getToken();
    }

    /**
     * @deprecated since version 2.6, to be removed in 3.0. Use TokenStorageInterface::setToken() instead.
     *
     * {@inheritdoc}
     */
    public function setToken(TokenInterface $token = null)
    {
        return $this->tokenStorage->setToken($token);
    }

    /**
     * @deprecated since version 2.6, to be removed in 3.0. Use AuthorizationCheckerInterface::isGranted() instead.
     *
     * {@inheritdoc}
     */
    public function isGranted($attributes, $object = null)
    {
        return $this->authorizationChecker->isGranted($attributes, $object);
    }

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
        //$oid = new ObjectIdentity('class', get_class($object));
        return $this->isGranted(self::PERMISSION_CREATE, $object);
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
}