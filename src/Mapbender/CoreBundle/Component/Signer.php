<?php
namespace Mapbender\CoreBundle\Component;

use ArsGeografica\Signing\BadSignatureException;
use ArsGeografica\Signing\Signer as BaseSigner;

/**
 * Class Signer
 */
class Signer extends BaseSigner
{
    /**
     * Sign a URL by signing the protocol, server and path part and returning a signature including the signed
     * parts length.
     *
     * This gives a hint for checking the URL that only the left-most n chars are required to match the signature.
     *
     * @param   string  $url
     * @return  string  $signedUrl  Signed url, with signature included as _sign parameter
     */
    public function signUrl($url)
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
    public function getSignature($url)
    {
        // cut URL at first slash after hostname / port
        // => allow all requests to same scheme + host + port (+ username + password for basic auth)
        $baseUrl = preg_replace('#(?<=[^:/])/.*$#', '', $url);
        return implode($this->sep, array(
            strlen($baseUrl),
            $this->signature($baseUrl),
        ));
    }

    /**
     * @param string $url
     * @throws BadSignatureException
     */
    public function checkSignedUrl($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $params);
        if(!isset($params['_signature'])) {
            throw new BadSignatureException('No URL signature provided');
        }
        $compareSignature = $this->getSignature($url);
        if ($compareSignature !== $params['_signature']) {
            throw new BadSignatureException('Signature mismatch');
        }
    }
}
