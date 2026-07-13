<?php


namespace FOM\UserBundle\Component\Ldap;

use LDAP\Connection;

/**
 * Simple ldap client
 * Service registered as fom.ldap_client
 *
 * @since v3.1.7
 * @since v3.2.7
 */
class Client
{
    /** @var int */
    protected $port;

    // NOTE: Connection type changed from resource => object in PHP8.1.
    // See https://www.php.net/manual/en/function.ldap-connect.php
    /** @var resource|Connection */
    protected $connection;

    /**
     * @param string $host
     * @param int $port
     * @param int $version
     * @param string $bindDn
     * @param string|null $bindPassword
     */
    public function __construct(protected $host, $port, protected $version,
                                protected $bindDn, protected $bindPassword)
    {
        /** @todo: TLS support (active TLS should change default port) */
        $this->port = $port ?: 389;
    }

    /**
     * Opens the connection. Can be called safely multiple times, does nothing after the first successful
     * invocation.
     *
     * @return bool to indicate (prior) success
     * @throws ConnectionException
     */
    public function bind(): bool
    {
        if (!$this->connection && $this->host) {
            $dsn = "ldap://{$this->host}:{$this->port}";
            $this->connection = @ldap_connect($dsn);
            if (!$this->connection) {
                throw new ConnectionException("Can't connect to {$dsn}.");
            }
            if (!ldap_set_option($this->connection, LDAP_OPT_PROTOCOL_VERSION, $this->version)) {
                throw new ConnectionException("Can't set protocol version {$this->version}: " . ldap_error($this->connection));
            }
            if (!@ldap_bind($this->connection, $this->bindDn, $this->bindPassword)) {
                throw new BindException("Can't bind to {$dsn} as '" . print_r($this->bindDn, true) . ": " . ldap_error($this->connection));
            }
        }
        return !!$this->connection;
    }

    /**
     * @return string
     */
    public function getDsn(): string
    {
        /** @todo: TLS support (active TLS should change protocol prefix to 'ldaps') */
        return "ldap://{$this->host}:{$this->port}";
    }

    /**
     * Returns a list of ldap objects matching given base dn and filter formatted into arrays.
     * Each object entry array is a mapping of attribute name => array of values (every ldap attribute can potentially
     * have multiple assigned values).
     *
     * NOTE: all attribute names are lower-cased
     * @see https://www.php.net/manual/en/function.ldap-get-entries.php
     *
     * @param string $baseDn
     * @param string $filter
     * @return array[][]
     */
    public function getObjects($baseDn, $filter): array
    {
        if ($this->bind()) {
            $listResponse = @ldap_list($this->connection, $baseDn, $filter);
            if ($listResponse === false) {
                throw new \InvalidArgumentException("Can't list {$baseDn} objects with filter {$filter}: " . ldap_error($this->connection));
            }
            $rawResponse = ldap_get_entries($this->connection, $listResponse);
            $result = [];
            unset($rawResponse['count']);
            if (!empty($rawResponse)) {
                foreach ($rawResponse as $rawEntry) {
                    if (!is_array($rawEntry)) {
                        continue;
                    }
                    $entry = [];
                    foreach ($rawEntry as $attributeName => $attributeData) {
                        if (is_array($attributeData)) {
                            unset($attributeData['count']);
                            $entry[$attributeName] = array_values($attributeData);
                        }
                    }
                    $result[] = $entry;
                }
            }
            ldap_free_result($listResponse);
            return $result;
        } else {
            return [];
        }
    }
}
