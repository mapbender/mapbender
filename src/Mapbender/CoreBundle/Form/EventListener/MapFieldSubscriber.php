<?php

namespace Mapbender\CoreBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * 
 */
class MapFieldSubscriber implements EventSubscriberInterface
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
            FormEvents::PRE_BIND     => 'preBind');
    }

    /**
     * Checkt form fields by PRE_BIND FormEvent
     * 
     * @param FormEvent $event
     */
    public function preBind(FormEvent $event)
    {
        $data = $event->getData();

        if(null === $data)
        {
            return;
        }
        if(key_exists("otherSrs", $data) && is_string($data["otherSrs"]))
        {
            $data["otherSrs"] = preg_split("/\s?,\s?/", $data["otherSrs"]);
            $event->setData($data);
        }
        if(key_exists("scales", $data) && is_string($data["scales"]))
        {
            $scales         = preg_split("/\s?[\,\;]+\s?/", $data["scales"]);
            $scales         = array_map(create_function('$value',
                    'return (int)$value;'), $scales);
            arsort($scales, SORT_NUMERIC);
            $data["scales"] = $scales;
            $event->setData($data);
        }
    }

    /**
     * Checkt form fields by PRE_SET_DATA FormEvent
     * 
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if(null === $data)
        {
            return;
        }

        if(key_exists("otherSrs", $data) && is_array($data["otherSrs"]))
        {
            $data["otherSrs"] = implode(",", $data["otherSrs"]);
            $event->setData($data);
        }
        if(key_exists("scales", $data) && is_array($data["scales"]))
        {
            $data["scales"] = implode(",", $data["scales"]);
            $event->setData($data);
        }
    }

}