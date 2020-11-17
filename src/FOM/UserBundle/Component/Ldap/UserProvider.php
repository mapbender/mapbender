<?php


namespace FOM\UserBundle\Component\Ldap;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Model\SecurityIdentityInterface;

/**
 * Service registered as fom.ldap_user_identities_provider
 * @since v3.1.7
 * @since v3.2.7
 */
class UserProvider
{
    /** @var Client */
    protected $client;
    /** @var string */
    protected $userClass;
    /** @var string */
    protected $baseDn;
    /** @var string */
    protected $nameAttribute;
    /** @var string */
    protected $filter;
    /** @var int */
    protected $preloadCountdown = 1;
    /** @var array|null */
    protected $preloadData;

    /**
     * @param Client $client
     * @param string $userClass for generated UserSecurityIdentity objects
     * @param string $baseDn
     * @param string $nameAttribute
     * @param string|null $filter extra LDAP filter
     */
    public function __construct(Client $client, $userClass, $baseDn, $nameAttribute, $filter)
    {
        $this->client = $client;
        $this->userClass = $userClass;
        $this->baseDn = $baseDn;
        $this->nameAttribute = $nameAttribute;
        // remove optional enclosing brackets around filter (at most one level);
        $this->filter = preg_replace('/(^\()|(\)$)/', '', $filter ?: '');
    }

    /**
     * @param string $pattern
     * @return SecurityIdentityInterface[]
     */
    public function getIdentities($pattern = '*')
    {
        $filter = $this->getFilterString($pattern);
        $users = array();
        foreach ($this->client->getObjects($this->baseDn, $filter) as $userRecord) {
            $users[] = $this->transformUserRecord($userRecord);
        }
        return $users;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function userExists($name)
    {
        if ($this->preloadCountdown) {
            // NOTE: ldap_escape implementation is provided by symfony/polyfill-php56 even on older PHP versions,
            //       but only if PHP Ldap extension is installed and activated
            if (function_exists('\ldap_escape')) {
                $pattern = \ldap_escape($name, null, LDAP_ESCAPE_FILTER);
                --$this->preloadCountdown;
                return !!$this->getIdentities($pattern);
            } else {
                return false;
            }
        } else {
            if ($this->preloadData === null) {
                $this->initPreload();
            }
            return !empty($this->preloadData[$name]);
        }
    }

    /**
     * @param array[] $record
     * @return SecurityIdentityInterface
     */
    protected function transformUserRecord($record)
    {
        return new UserSecurityIdentity($record[$this->nameAttribute][0], $this->userClass);
    }

    /**
     * @param string $namePattern
     * @return string
     */
    protected function getFilterString($namePattern)
    {
        $baseFilter = "({$this->nameAttribute}={$namePattern})";
        if ($this->filter) {
            return "(&{$baseFilter}({$this->filter}))";
        } else {
            return $baseFilter;
        }
    }

    protected function initPreload()
    {
        $this->preloadData = array();
        foreach ($this->getIdentities('*') as $ident) {
            if ($ident instanceof UserSecurityIdentity) {
                $name = $ident->getUsername();
            } elseif (is_object($ident) && method_exists($ident, 'getUsername')) {
                $name = $ident->getUsername();
            } elseif (is_object($ident) && property_exists($ident, 'getUsername')) {
                // support legacy stdClass with lambda property
                $name = $ident->getUsername;
                if ($name instanceof \Closure) {
                    $name = $name();
                }
            } else {
                $t = is_object($ident) ? get_class($ident) : gettype($ident);
                throw new \RuntimeException("Cannot find user name on type {$t} user identity");
            }
            $this->preloadData[$name] = $ident;
        }
    }
}
