<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface TransportInterface
{
    /**
     * Transport constructor.
     * @param $proxyHost
     * @param $proxyPort
     * @param $proxyUser
     * @param $proxyPass
     * @param $proxyExclude
     * @param $connectTimeout
     * @param $timeout
     */
    public function __construct($proxyHost, $proxyPort, $proxyUser, $proxyPass, $proxyExclude, $connectTimeout, $timeout);

    /**
     * @param Request $request
     * @return Response
     * @throws TransportException
     */
    public function handle(Request $request);

    /**
     * @return string
     */
    public function getProxyHost();

    /**
     * @param string $proxyHost
     * @return Transport
     */
    public function setProxyHost($proxyHost);

    /**
     * @return string
     */
    public function getProxyPort();

    /**
     * @param string $proxyPort
     * @return Transport
     */
    public function setProxyPort($proxyPort);

    /**
     * @return string
     */
    public function getProxyUser();

    /**
     * @param string $proxyUser
     * @return Transport
     */
    public function setProxyUser($proxyUser);

    /**
     * @return string
     */
    public function getProxyPass();

    /**
     * @param string $proxyPass
     * @return Transport
     */
    public function setProxyPass($proxyPass);

    /**
     * @return array
     */
    public function getProxyExclude();

    /**
     * @param array $proxyExclude
     * @return Transport
     */
    public function setProxyExclude($proxyExclude);

    /**
     * @return int
     */
    public function getConnectTimeout();

    /**
     * @param int $connectTimeout
     * @return Transport
     */
    public function setConnectTimeout($connectTimeout);

    /**
     * @return int
     */
    public function getTimeout();

    /**
     * @param int $timeout
     * @return Transport
     */
    public function setTimeout($timeout);

    /**
     * @return bool
     */
    public function isFollowRedirect();

    /**
     * @param bool $followRedirect
     * @return Transport
     */
    public function setFollowRedirect($followRedirect);

    /**
     * @return int
     */
    public function getMaxRedirects();

    /**
     * @param int $maxRedirects
     * @return Transport
     */
    public function setMaxRedirects($maxRedirects);
}