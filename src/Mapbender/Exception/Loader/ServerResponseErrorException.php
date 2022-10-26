<?php


namespace Mapbender\Exception\Loader;


class ServerResponseErrorException extends SourceLoaderException
{
    public function __construct($message = "")
    {
        parent::__construct($message, 0, null);
    }
}
