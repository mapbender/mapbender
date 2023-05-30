<?php


namespace FOM\UserBundle\Component;


use FOM\UserBundle\Entity\Group;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Controls which security identities will be listed when picking security identities to add to an Acl.
 *
 * This is a post-filter because FOMIdentitiesProvider implementations have a long, bad history of
 * customization, with lots of different value types returned, and no angle to inject new configured
 * behaviours.
 *
 * @since v3.1.12
 * @since v3.2.12
 */
class AssignableSecurityIdentityFilter
{
    /** @var IdentitiesProviderInterface */
    protected $provider;
    /** @var bool */
    protected $allowUsers;
    /** @var bool */
    protected $allowGroups;
    /** @var bool */
    protected $allowAuthenticated;
    /** @var bool */
    protected $allowAnonymous;

    protected $warningMessages = array();

    /** @var DummyGroup */
    protected $anonGroup;
    /** @var DummyGroup */
    protected $authenticatedGroup;

    /**
     * @param IdentitiesProviderInterface $provider
     * @param bool $showUsers
     * @param bool $showGroups
     * @param bool $showAuthenticated
     * @param bool $showAnonymous
     */
    public function __construct(IdentitiesProviderInterface $provider,
                                $showUsers, $showGroups, $showAuthenticated, $showAnonymous)
    {
        $this->provider = $provider;
        $this->allowUsers = $showUsers;
        $this->allowGroups = $showGroups;
        $this->allowAuthenticated = $showAuthenticated;
        $this->allowAnonymous = $showAnonymous;
        $this->anonGroup = new DummyGroup('IS_AUTHENTICATED_ANONYMOUSLY', 'fom.acl.group_label.anonymous');
        $this->authenticatedGroup = new DummyGroup('ROLE_USER', 'fom.acl.group_label.authenticated');
    }

    /**
     * @return Group[]
     */
    public function getAssignableGroups()
    {
        $builtInRoles = array(
            'ROLE_USER',
            'IS_AUTHENTICATED_ANONYMOUSLY',
        );
        $groups = array();
        if ($this->allowAuthenticated) {
            $groups[] = $this->authenticatedGroup;
        }
        if ($this->allowGroups) {
            foreach ($this->provider->getAllGroups() as $providerGroup) {
                $groupIdent = $this->normalizeGroup($providerGroup);
                if (!\in_array($groupIdent->getRole(), $builtInRoles)) {
                    $groups[] = $groupIdent;
                }
            }
        }
        if ($this->allowAnonymous) {
            $groups[] = $this->anonGroup;
        }
        return $groups;
    }

    /**
     * @return UserSecurityIdentity[]
     */
    public function getAssignableUsers()
    {
        $users = array();
        if ($this->allowUsers) {
            foreach ($this->provider->getAllUsers() as $providerUser) {
                $users[] = $this->normalizeUser($providerUser);
            }
        }
        return $users;
    }

    /**
     * @param mixed $value
     * @return DummyGroup
     * @throws \InvalidArgumentException
     */
    protected function normalizeGroup($value)
    {
        $title = null;
        $role = null;
        if (\is_string($value)) {
            $role = $value;
        } elseif (\is_object($value) && !($value instanceof \stdClass)) {
            foreach (array('getRole', 'getAsRole', '__toString') as $roleMethodCandidate) {
                if (\method_exists($value, $roleMethodCandidate)) {
                    $role = $value->{$roleMethodCandidate}();
                    break;
                }
            }
            if (!$role) {
                throw new \InvalidArgumentException("Group object " . \get_class($value) . " doesn't have a role getter");
            }
            if (\method_exists($value, 'getTitle')) {
                $title = $value->getTitle();
            }
        } elseif (\is_object($value)) {
            $values = $this->extractStdClass($value);
            $role = '';
            foreach (array('role', 'getRole', 'getAsRole') as $roleCandidate) {
                if (!empty($values[$roleCandidate])) {
                    $role = $values[$roleCandidate];
                    break;
                }
            }
            if (!$role) {
                throw new \InvalidArgumentException("StdClass group object doesn't have a role getter");
            }
            foreach (array('title', 'getTitle') as $titleCandidate) {
                if (!empty($values[$titleCandidate])) {
                    $title = $values[$titleCandidate];
                    break;
                }
            }
        } else {
            throw new \InvalidArgumentException("Don't know how to extract role from " . gettype($value) . " group input");
        }
        $title = $title ?: \preg_replace('#^ROLE_(GROUP_)?#', '', $role);
        return new DummyGroup($role, $title);
    }

    /**
     * @param mixed $value
     * @return UserSecurityIdentity
     * @throws \InvalidArgumentException
     */
    protected function normalizeUser($value)
    {
        if (is_object($value)) {
            if ($value instanceof UserSecurityIdentity) {
                return $value;
            }
            $cls = get_class($value);
            $this->warnOnce("User identities should be UserSecurityIdentity, not {$cls} objects", "user:{$cls}");
            if ($value instanceof UserInterface) {
                return UserSecurityIdentity::fromAccount($value);
            } elseif ($value instanceof \stdClass) {
                $values = $this->extractStdClass($value);
                $username = null;
                $userclass = null;
                foreach (array('username', 'getUsername') as $nameCandidate) {
                    if (!empty($values[$nameCandidate])) {
                        $username = $values[$nameCandidate];
                        break;
                    }
                }
                foreach (array('class', 'getClass') as $classCandidate) {
                    if (!empty($values[$classCandidate])) {
                        $userclass = $values[$classCandidate];
                        break;
                    }
                }
                if ($userclass && $username) {
                    return new UserSecurityIdentity($username, $userclass);
                }
                throw new \InvalidArgumentException("Don't know how to transform stdClass user input with keys " . implode(',', array_keys($values)) . " to UserSecurityIdentity");
            } else {
                throw new \InvalidArgumentException("Don't know how to transform {$cls} user input to UserSecurityIdentity");
            }
        } else {
            throw new \InvalidArgumentException("Don't know how to transform " . gettype($value) . " group input to UserSecurityIdentity");
        }
    }

    protected function warnOnce($message, $key = null)
    {
        $key = $key ?: $message;
        if (empty($this->warningMessages[$key])) {
            // @todo: add throwing strict mode
            // NOTE: E_USER_DEPRECATED is the only error class that will reliably go to the log without throwing an
            //       exception.
            @trigger_error("WARNING: {$message}", E_USER_DEPRECATED);
            $this->warningMessages[$key] = true;
        }
    }

    protected static function extractStdClass($o)
    {
        $values = array();
        foreach ((array)$o as $name => $value) {
            if (\is_callable($value)) {
                $values[$name] = $value();
            } else {
                $values[$name] = $value;
            }
        }
        return $values;
    }
}
