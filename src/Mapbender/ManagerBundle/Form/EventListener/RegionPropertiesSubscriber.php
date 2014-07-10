<?php

namespace Mapbender\ManagerBundle\Form\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * 
 */
class RegionPropertiesSubscriber implements EventSubscriberInterface
{

    /**
     * A FormFactoryInterface 's Factory
     * 
     * @var \Symfony\Component\Form\FormFactoryInterface 
     */
    private $factory;

    /**
     * The application
     * 
     * @var options
     */
    private $options;

    /**
     * Creates a subscriber
     * 
     * @param \Symfony\Component\Form\FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory, $options)
    {
        $this->factory = $factory;
        $this->options = $options;
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
     * Checks the form fields by PRE_BIND FormEvent
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
        if (key_exists("name", $data) && isset($this->options['available_properties'][$data['name']])) {
            $choices = array();
            foreach ($this->options['available_properties'][$data['name']] as $key => $value) {
                $choices[$key] = $key;
            }
            $form->add($this->factory->createNamed(
                    'properties', "choice", null, array(
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => $choices,
                    'auto_initialize' => false
            )));
        }
    }

    /**
     * Checks the form fields by PRE_SET_DATA FormEvent
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
        if ($data->getName() !== null && isset($this->options['available_properties'][$data->getName()])) {
            $choices = array();
            foreach ($this->options['available_properties'][$data->getName()] as $key => $value) {
                $choices[$key] = $key;
            }
            $form->add($this->factory->createNamed(
                    'properties', "choice", null, array(
                    'expanded' => true,
                    'multiple' => true,
                    'choices' => $choices,
                    'auto_initialize' => false
            )));
        }
    }

}
