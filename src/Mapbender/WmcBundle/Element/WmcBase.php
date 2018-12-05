<?php


namespace Mapbender\WmcBundle\Element;


use Mapbender\CoreBundle\Component\Element;
use Mapbender\WmcBundle\Component\WmcHandler;

abstract class WmcBase extends Element
{
    /**
     * @return WmcHandler
     */
    protected function wmcHandlerFactory()
    {
        return new WmcHandler($this->getEntity()->getApplication(), $this->container);
    }
}
