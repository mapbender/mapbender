<?php

namespace Mapbender\PrintBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Mapbender\PrintBundle\Element\Type\PrintClientQualityAdminType;

/**
 * 
 */
class PrintClientSubscriber implements EventSubscriberInterface
{

    /** @var FormFactoryInterface */
    private $factory;

    /**
     * @param FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preBind');
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
            $form->add($this->factory->createNamed(
                    'quality_levels', "collection", null, array(
                    'property_path' => '[quality_levels]',
                    'auto_initialize' => false,
                    'required' => false,
                    'type' => new PrintClientQualityAdminType(),
                    'options' => array(
            ))));
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

        if (key_exists("quality_levels", $data)) {
            $form->add($this->factory->createNamed(
                    'quality_levels', "collection", null, array(
                    'property_path' => '[quality_levels]',
                    'auto_initialize' => false,
                    'required' => false,
                    'type' => new PrintClientQualityAdminType(),
                    'options' => array(
            ))));
        }
    }

}
