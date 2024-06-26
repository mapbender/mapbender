<?php


namespace OwsProxy3\CoreBundle\Component;

use Mapbender\Component\Transport\ConnectionErrorException;
use Mapbender\Component\Transport\ConnectionTimeoutException;
use OwsProxy3\CoreBundle\Controller\OwsProxyController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Default implementation for service owsproxy.http_foundation_client.
 *
 * Handles ProxyQuery requests and returns Symfony HttpFoundation Responses
 *
 * Does not care about cookies or sessions or signatures.
 * Use this service to replace internal direct usages of CommonProxy.
 * Use this service to replace internal kernel subrequests to
 * @see OwsProxyController::genericProxyAction()
 *
 * @since v3.1.6
 */
class HttpFoundationClient extends CurlClientCommon
{
    /**
     * Handles the request and returns the response.
     *
     * @param ProxyQuery $query
     * @return Response
     * @throws \Exception
     */
    public function handleQuery(ProxyQuery $query)
    {
        $this->logger->debug("HttpFoundationClient::handleQuery {$query->getMethod()}", array(
            'url' => $query->getUrl(),
            'headers' => $query->getHeaders(),
        ));
        return $this->handleQueryInternal($query);
    }

    /**
     * @param ProxyQuery $query
     * @return Response
     */
    protected function handleQueryInternal(ProxyQuery $query)
    {
        $ch = $this->openHandle($query);
        $rawResponse = \curl_exec($ch);
        if ($rawResponse !== false) {
            $response = $this->parseResponse($ch, $rawResponse);
            \curl_close($ch);
        } else {
            $curlError = \curl_error($ch);
            $errorCode = \curl_errno($ch);
            \curl_close($ch);
            switch ($errorCode) {
                case 6: // CURLE_COULDNT_RESOLVE_HOST
                case 7: // CURLE_COULDNT_CONNECT
                    throw new ConnectionErrorException($curlError, $errorCode);
                case 5: //CURLE_COULDNT_RESOLVE_PROXY
                    throw new ConnectionErrorException('', $errorCode);
                case 28: // CURLE_OPERATION_TIMEDOUT
                    throw new ConnectionTimeoutException($curlError, $errorCode);
                default:
                    // Uh-oh
                    $response = new Response('');
                    $response->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE, $curlError ?: null);
            }
        }
        return $response;
    }

    /**
     * @param resource $ch
     * @param string|false $rawResponse
     * @return Response
     */
    protected function parseResponse($ch, $rawResponse)
    {
        $headerLength = strlen($rawResponse) - \curl_getinfo($ch, CURLINFO_SIZE_DOWNLOAD);
        $body = substr($rawResponse, $headerLength);
        $response = new Response($body, \curl_getinfo($ch, CURLINFO_HTTP_CODE));
        $responseHeaders = $this->parseResponseHeaders(substr($rawResponse, 0, $headerLength));
        $responseHeaders =  Utils::filterHeaders($responseHeaders, array(
            'transfer-encoding',
        ));
        $response->headers->add($responseHeaders);
        return $response;
    }

    /**
     * @param ProxyQuery $query
     * @return resource
     */
    protected function openHandle(ProxyQuery $query)
    {
        $options = $this->getCurlOptions($query->getHostName(), $this->proxyParams);
        $headers = $this->prepareRequestHeaders($query);
        if ($headers) {
            $options[CURLOPT_HTTPHEADER] = $this->flattenHeaders($headers);
        }
        if ($query->getMethod() === 'POST') {
            $options[CURLOPT_CUSTOMREQUEST] = 'POST';
            $options[CURLOPT_POSTFIELDS] = $query->getContent() ?: '';
        }
        $ch = \curl_init($query->getUrl());
        if ($ch === false) {
            throw new \RuntimeException("Cannot open curl handle");
        }
        \curl_setopt_array($ch, $options);
        return $ch;
    }
}
