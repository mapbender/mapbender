<?php

namespace OwsProxy3\CoreBundle\Event;

use OwsProxy3\CoreBundle\Component\Url;
use Symfony\Component\EventDispatcher\Event;

/**
 * Description of BeforeProxyEvent
 *
 * @author apour
 */
class BeforeProxyEvent extends Event {
    protected $url;
    
    public function __construct(Url $url) {
        $this->url = $url;
    }
}
