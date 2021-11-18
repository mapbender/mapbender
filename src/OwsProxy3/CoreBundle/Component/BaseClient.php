<?php


namespace OwsProxy3\CoreBundle\Component;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BaseClient
{
    /** @var string */
    const DEFAULT_USER_AGENT = 'OWSProxy3';

    /** @var array */
    protected $proxyParams;
    /** @var string */
    protected $userAgent;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(array $proxyParams, $userAgent = null, LoggerInterface $logger = null)
    {
        if (empty($proxyParams['host'])) {
            $proxyParams = array();
        }
        $this->proxyParams = $proxyParams;
        $this->userAgent = $userAgent ?: self::DEFAULT_USER_AGENT;
        $this->logger = $logger ?: new NullLogger();
    }
}
