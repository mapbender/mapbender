<?php


namespace Mapbender\CoreBundle\Component\Exception;


class UndefinedElementClassException extends ElementErrorException
{
    public function __construct($componentClassName, $message = '', $previous = null)
    {
        $message = $message ?: 'No such class ' . print_r($componentClassName, true);
        parent::__construct($componentClassName, $message, 0, $previous);
    }
}
