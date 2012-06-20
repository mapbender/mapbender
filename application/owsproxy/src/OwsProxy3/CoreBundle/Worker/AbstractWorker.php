<?php

namespace OwsProxy3\CoreBundle\Worker;

use Symfony\Component\DependencyInjection\ContainerInterface;
use OwsProxy3\CoreBundle\Event\AfterProxyEvent;
use OwsProxy3\CoreBundle\Event\BeforeProxyEvent;

interface AbstractWorker {
    public function __construct(ContainerInterface $container);
    public function onBeforeProxyEvent(BeforeProxyEvent $event);
    public function onAfterProxyEvent(AfterProxyEvent $event);
}
