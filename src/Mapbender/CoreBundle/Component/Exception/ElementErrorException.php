<?php


namespace Mapbender\CoreBundle\Component\Exception;


class ElementErrorException extends \RuntimeException
{
    /** @var string */
    protected $componentClassName;

    /**
     * @param string $componentClassName
     * @param string $message
     * @param int $code
     * @param \Throwable|null $previous
     */
    public function __construct($componentClassName, $message='', $code=0, $previous=null)
    {
        parent::__construct($message, $code, $previous);
        $this->componentClassName = $componentClassName;
    }
}
