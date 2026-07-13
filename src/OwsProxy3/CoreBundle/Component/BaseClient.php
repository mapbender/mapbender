<?php


namespace OwsProxy3\CoreBundle\Component;


use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class BaseClient
{
    /** @var string */
    const DEFAULT_USER_AGENT = 'OWSProxy3';

    protected array $proxyParams;
    /** @var string */
    protected $userAgent;
    protected LoggerInterface $logger;

    public function __construct(array $proxyParams, $userAgent = null, ?LoggerInterface $logger = null)
    {
        if (empty($proxyParams['host'])) {
            $proxyParams = [];
        }
        $this->proxyParams = $proxyParams;
        $this->userAgent = $userAgent ?: self::DEFAULT_USER_AGENT;
        $this->logger = $logger ?: new NullLogger();
    }
}
