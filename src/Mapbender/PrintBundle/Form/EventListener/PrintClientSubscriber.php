<?php

namespace Mapbender\PrintBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Mapbender\PrintBundle\Element\Type\PrintClientQualityAdminType;

/**
 * 
 */
class PrintClientSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preBind',
        );
    }

    /**
     * Checkt form fields by PRE_SUBMIT FormEvent
     * 
     * @param FormEvent $event
     */
    public function preBind(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
        if (null === $data) {
            return;
        }
        if (key_exists("scales", $data) && is_string($data["scales"])) {
            $data["scales"] = preg_split("/\s?,\s?/", $data["scales"]);
            $event->setData($data);
        }

        if (key_exists("quality_levels", $data)) {
            $form->add('quality_levels', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'auto_initialize' => false,
                'required' => false,
                'entry_type' => 'Mapbender\PrintBundle\Element\Type\PrintClientQualityAdminType',
            ));
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
        $form = $event->getForm();
        if (null === $data) {
            return;
        }

        if (key_exists("scales", $data) && is_array($data["scales"])) {
            $data["scales"] = implode(",", $data["scales"]);
            $event->setData($data);
        }

        if (array_key_exists("quality_levels", $data)) {
            $form->add('quality_levels', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'auto_initialize' => false,
                'required' => false,
                'entry_type' => 'Mapbender\PrintBundle\Element\Type\PrintClientQualityAdminType',
            ));
        }
    }

}
