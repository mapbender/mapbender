<?php


namespace Mapbender\CoreBundle\Component\Exception;


use Throwable;

class ProxySignatureEmptyException extends ProxySignatureException
{
    public function __construct($message = "Missing signature", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
