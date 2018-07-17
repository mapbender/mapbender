<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class Transport
 * @package Mapbender\CoreBundle\Component
 */
class Transport implements TransportInterface
{
    protected $proxyHost = '';
    protected $proxyPort = '';
    protected $proxyUser = '';
    protected $proxyPass = '';
    protected $proxyExclude = [];
    protected $connectTimeout = 60;
    protected $timeout = 90;
    protected $followRedirect = true;
    protected $maxRedirects = 10;

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
    public function __construct($proxyHost, $proxyPort, $proxyUser, $proxyPass, $proxyExclude, $connectTimeout, $timeout)
    {
        $this->proxyHost = $proxyHost;
        $this->proxyPort = $proxyPort;
        $this->proxyUser = $proxyUser;
        $this->proxyPass = $proxyPass;
        $this->proxyExclude = $proxyExclude;
        $this->connectTimeout = $connectTimeout;
        $this->timeout = $timeout;
    }

    /**
     * @param Request $request
     * @return Response
     * @throws TransportException
     */
    public function handle(Request $request)
    {
        $init = curl_init($request->getUri());
        $urlInfo  = parse_url($request->getUri());

        if (isset($urlInfo['scheme']) && $urlInfo['scheme'] !== 'http' && $urlInfo['scheme'] !== 'https') {
            throw new TransportException('Only http and https supported');
        }

        if ($request->getMethod() === 'GET') {
            curl_setopt($init, CURLOPT_POST, false);
        } elseif ($request->getMethod() === 'POST') {
            curl_setopt($init, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($init, CURLOPT_POSTFIELDS, $request->getContent());
        }

        if ($this->followRedirect) {
            curl_setopt($init, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($init, CURLOPT_MAXREDIRS, $this->maxRedirects);
        }

        curl_setopt($init, CURLOPT_AUTOREFERER, true);
        curl_setopt($init, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($init, CURLOPT_HEADER, true);
        curl_setopt($init, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout);
        curl_setopt($init, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($init, CURLINFO_HEADER_OUT, true);

        $symfonyRequestHeader = $request->headers->all();
        if (!empty($symfonyRequestHeader)) {
            $curlRequestHeader = [];
            foreach ($symfonyRequestHeader as $key => $values) {
                foreach ($values as $value) {
                    $curlRequestHeader[] = ucfirst($key) . ": " . $value;
                }
            }
            curl_setopt($init, CURLOPT_HTTPHEADER, $curlRequestHeader);
        }

        if (!empty($this->proxyHost) && !in_array($urlInfo["host"], $this->proxyExclude)) {
            curl_setopt($init, CURLOPT_PROXY, $this->proxyHost);
            curl_setopt($init, CURLOPT_PROXYPORT, $this->proxyPort);

            if (!empty($this->proxyUser) && !empty($this->proxyPass)) {
                curl_setopt($init, CURLOPT_PROXYUSERPWD, $this->proxyUser . ':' . $this->proxyPass);
                curl_setopt($init, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
            }
        }

        $curlResponse = curl_exec($init);
        $error = curl_error($init);

        if (!empty($error)) {
            throw new TransportException($error);
        }

        $headerSize = curl_getinfo($init, CURLINFO_HEADER_SIZE);
        $content = substr($curlResponse, $headerSize);
        $status = (int)curl_getInfo($init, CURLINFO_HTTP_CODE);
        $header = substr($curlResponse, 0, $headerSize);
        $target = curl_getinfo($init, CURLINFO_EFFECTIVE_URL);

        curl_close($init);

        $response = new Response($content, $status, $this->parseHeaderInformation($header));

        if (preg_match('/^Location: (.+)$/im', $header, $matches)) {
            $response->isRedirect($target);
        }

        return $response;
    }

    /**
     * @param $raw
     * @return array
     */
    protected function parseHeaderInformation($raw)
    {
        $headers = [];

        // Split multiple headers
        preg_match_all('/(HTTP((?!\r\n\r\n).)+)\r\n\r\n?/s', $raw, $match);

        // Return if no headers found
        if (!isset($match[0]) || count($match[0]) < 1) {
            return $headers;
        }

        // Take last header and prepare for Symfony response.
        foreach (explode("\r\n", $match[0][count($match[0])-1]) as $header) {
            if (trim($header) != "" && strstr($header, ':')) {
                $key = trim(strtolower(strstr($header, ':', true)));
                $val = trim(substr(strstr($header, ':'), 1));
                $headers[$key] = $val;
            }
        }

        return $headers;
    }

    /**
     * @return string
     */
    public function getProxyHost()
    {
        return $this->proxyHost;
    }

    /**
     * @param string $proxyHost
     * @return Transport
     */
    public function setProxyHost($proxyHost)
    {
        $this->proxyHost = $proxyHost;
        return $this;
    }

    /**
     * @return string
     */
    public function getProxyPort()
    {
        return $this->proxyPort;
    }

    /**
     * @param string $proxyPort
     * @return Transport
     */
    public function setProxyPort($proxyPort)
    {
        $this->proxyPort = $proxyPort;
        return $this;
    }

    /**
     * @return string
     */
    public function getProxyUser()
    {
        return $this->proxyUser;
    }

    /**
     * @param string $proxyUser
     * @return Transport
     */
    public function setProxyUser($proxyUser)
    {
        $this->proxyUser = $proxyUser;
        return $this;
    }

    /**
     * @return string
     */
    public function getProxyPass()
    {
        return $this->proxyPass;
    }

    /**
     * @param string $proxyPass
     * @return Transport
     */
    public function setProxyPass($proxyPass)
    {
        $this->proxyPass = $proxyPass;
        return $this;
    }

    /**
     * @return array
     */
    public function getProxyExclude()
    {
        return $this->proxyExclude;
    }

    /**
     * @param array $proxyExclude
     * @return Transport
     */
    public function setProxyExclude($proxyExclude)
    {
        $this->proxyExclude = $proxyExclude;
        return $this;
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }

    /**
     * @param int $connectTimeout
     * @return Transport
     */
    public function setConnectTimeout($connectTimeout)
    {
        $this->connectTimeout = $connectTimeout;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return Transport
     */
    public function setTimeout($timeout)
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFollowRedirect()
    {
        return $this->followRedirect;
    }

    /**
     * @param bool $followRedirect
     * @return Transport
     */
    public function setFollowRedirect($followRedirect)
    {
        $this->followRedirect = $followRedirect;
        return $this;
    }

    /**
     * @return int
     */
    public function getMaxRedirects()
    {
        return $this->maxRedirects;
    }

    /**
     * @param int $maxRedirects
     * @return Transport
     */
    public function setMaxRedirects($maxRedirects)
    {
        $this->maxRedirects = $maxRedirects;
        return $this;
    }
}
