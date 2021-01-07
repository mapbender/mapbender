<?php


namespace Mapbender\CoreBundle\Component\Exception;


use Throwable;

class ProxySignatureInvalidException extends ProxySignatureException
{
    public function __construct($message = "Invalid signature", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
