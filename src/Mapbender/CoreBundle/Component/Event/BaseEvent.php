<?php

namespace Mapbender\CoreBundle\Component\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class BaseEvent
 *
 * @package   Mapbender\CoreBundle\Component\Event
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class BaseEvent extends Event
{
    /** @var mixed */
    protected $target;

    /**
     * @param mixed $target target
     */
    public function __construct($target = null)
    {
        $this->target = $target;
        $this->target;
    }

    /**
     * @return mixed
     */
    public function getTarget()
    {
        return $this->target;
    }

}