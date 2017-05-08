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
        $psp = substr($url, 0, strpos($url, '?'));
        $signature = sprintf('%d%s%s', strlen($psp), $this->sep, $this->signature($psp));
        $sep = (false === strstr($url, '?') ? '?' : '&');
        $sep = ('?' === substr($url, -1) || '&' === substr($url, -1) ? '' : $sep);

        return $url . $sep . '_signature=' . urlencode($signature);
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

        $inSignature = explode($this->sep, $params['_signature']);
        if(count($inSignature) < 2) {
            throw new BadSignatureException('Invalid signature layout.');
        }

        $this->unsign(substr($url, 0, $inSignature[0]) . $this->sep . $inSignature[1]);
    }
}
