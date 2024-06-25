<?php


namespace Mapbender\Component\Event;


use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class AbstractInitDbHandler implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return array(
            'mapbender.init.db' => ['onInitDb'],
        );
    }

    abstract public function onInitDb(InitDbEvent $event);
}
