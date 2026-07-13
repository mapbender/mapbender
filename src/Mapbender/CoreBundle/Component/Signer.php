<?php
namespace Mapbender\CoreBundle\Component;

use ArsGeografica\Signing\BadSignatureException;
use Mapbender\CoreBundle\Component\Exception\ProxySignatureEmptyException;
use Mapbender\CoreBundle\Component\Exception\ProxySignatureException;
use Mapbender\CoreBundle\Component\Exception\ProxySignatureInvalidException;

class Signer
{
    /**
     * @param string $secret
     */
    public function __construct(protected $secret)
    {
    }

    /**
     * Sign a URL by signing the protocol, server and path part and returning a signature including the signed
     * parts length.
     *
     * This gives a hint for checking the URL that only the left-most n chars are required to match the signature.
     *
     * @param   string  $url
     * @return  string  $signedUrl  Signed url, with signature included as _sign parameter
     */
    public function signUrl($url): string
    {
        $signature = $this->getSignature($url);
        if (!preg_match('#\?.+$#', rtrim($url, '?'))) {
            $paramSeparator = '?';
        } else {
            $paramSeparator = '&';
        }
        return rtrim($url, '?') . $paramSeparator . '_signature=' . urlencode($signature);
    }

    /**
     * Create a signature from the pre-query portion of the given $url.
     *
     * @param string $url
     * @return string
     */
    public function getSignature($url): string
    {
        // cut URL at first slash after hostname / port
        // => allow all requests to same scheme + host + port (+ username + password for basic auth)
        $baseUrl = preg_replace('#(?<=[^:/])/.*$#', '', $url);
        return implode(':', [
            strlen((string) $baseUrl),
            $this->hashBase64($baseUrl),
        ]);
    }

    /**
     * @param string $url
     * @throws ProxySignatureException
     */
    public function checkSignedUrl($url): void
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        if (empty($params['_signature'])) {
            if (class_exists('\ArsGeografica\Signing\BadSignatureException')) {
                // Old owsproxy < v3.0.6.5
                throw new BadSignatureException('No URL signature provided');
            } else {
                throw new ProxySignatureEmptyException();
            }
        }
        $compareSignature = $this->getSignature($url);
        if ($compareSignature !== $params['_signature']) {
            if (class_exists('\ArsGeografica\Signing\BadSignatureException')) {
                // Old owsproxy < v3.0.6.5
                throw new BadSignatureException('Signature mismatch');
            } else {
                throw new ProxySignatureInvalidException();
            }
        }
    }

    /**
     * Return a URL-safe base64-encoded hash of the input $value
     *
     * @param string $value
     * @return string
     */
    public function hashBase64($value): string
    {
        $binaryHash = hash_hmac('sha1', $value, $this->secret . 'signer', true);
        $base64 = base64_encode($binaryHash);
        $base64UrlSafe = str_replace(['+', '/'], ['-', '_'], $base64);
        return rtrim($base64UrlSafe, '=');
    }
}
