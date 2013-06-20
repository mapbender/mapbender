<?php

namespace Mapbender\WmcBundle\Form\EventListener;

use Mapbender\CoreBundle\Entity\State;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * 
 */
class WmcFieldSubscriber implements EventSubscriberInterface
{

    /**
     * @inheritdoc
     */
    public function __construct(FormFactoryInterface $factory)
    {
        
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_BIND => 'preBind');
    }

    /**
     * Checkt form fields by PRE_BIND DataEvent
     * 
     * @param DataEvent $event
     */
    public function preBind(DataEvent $event)
    {
        $data = $event->getData();

        if(null === $data)
        {
            return;
        }
        if(key_exists("state", $data) && strlen($data["state"]) > 0)
        {
            $state = new State();
            $state->setJson(json_decode($data["state"]));
            $data["state"] = $state;
            $event->setData($data);
        }
    }

    /**
     * Checkt form fields by PRE_SET_DATA DataEvent
     * 
     * @param DataEvent $event
     */
    public function preSetData(DataEvent $event)
    {
        $data = $event->getData();
        if(null === $data)
        {
            return;
        }
    }

}