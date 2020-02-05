<?php


namespace Mapbender\Component\Event;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractInitDbHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'mapbender.init.db' => array(
                'onInitDb',
            ),
        );
    }

    abstract public function onInitDb(InitDbEvent $event);
}
