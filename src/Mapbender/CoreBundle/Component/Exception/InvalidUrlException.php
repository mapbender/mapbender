<?php


namespace Mapbender\CoreBundle\Component\Exception;


class InvalidUrlException extends \InvalidArgumentException
{
    public function __construct($url)
    {
        parent::__construct("Invalid url " . var_export($url, true));
    }
}
