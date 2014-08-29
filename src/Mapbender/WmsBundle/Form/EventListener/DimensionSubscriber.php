<?php

namespace Mapbender\WmsBundle\Form\EventListener;

use Mapbender\WmsBundle\Component\DimensionInterval;
use Mapbender\WmsBundle\Component\DimensionMultiple;
use Mapbender\WmsBundle\Component\DimensionMultipleInterval;
use Mapbender\WmsBundle\Component\DimensionSingle;
use Mapbender\WmsBundle\Form\Type\DimensionMultipleType;
use Mapbender\WmsBundle\Form\Type\DimensionIntervalType;
use Mapbender\WmsBundle\Form\Type\DimensionSingleType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * DimensionSubscriber class
 */
class DimensionSubscriber implements EventSubscriberInterface
{

    /**
     * A DimensionSubscriber's Factory
     * 
     * @var \Symfony\Component\Form\FormFactoryInterface 
     */
    private $factory;

    /**
     * Creates an instance
     * 
     * @param \Symfony\Component\Form\FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Returns defined events
     * 
     * @return array events
     */
    public static function getSubscribedEvents()
    {
        return array(FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit');
    }

    /**
     * Checkt form fields by PRE_SUBMIT FormEvent
     * 
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }

        if ($data) {
            $norm = $form->getNormData();
            if ($norm instanceof DimensionSingle) {
                
            } elseif ($norm instanceof DimensionInterval) {
////                $data->getDimension()->setExtent($data->getDimension()->extentStr());
//                $extent = $data->getDimension()->getExtent();
//                $form->add($this->factory->createNamed(
//                        'dimension', new DimensionIntervalType(), null,
//                        array(
//                        'data_class' => 'Mapbender\WmsBundle\Component\DimensionInterval',
//                        'start' => $data['start'],
//                        'end' => $data['end'],
//                        'interval' =>  $data['interval'],
//                        "required" => false,
//                        'auto_initialize' => false)));
            } elseif ($norm instanceof DimensionMultiple) {
////                $norm->getDimension()->setExtent($data['dimension']['extent']);
////                $norm->setUse($data['use']);
////                $a = 0;
//                $origExtent = $norm->getOrigExtent();
//                $choices = array_combine($origExtent, $origExtent);
//                $form->add($this->factory->createNamed(
//                        'dimension', new DimensionMultipleType(), null,
//                        array(
//                        'data_class' => 'Mapbender\WmsBundle\Component\DimensionMultiple',
//                        'origExtent' => $choices,
//                        'auto_initialize' => false)));
            } else {
                # TODO not supported yet
            }
        }
    }

    /**
     * Presets a form data
     * 
     * @param FormEvent $event
     * @return type
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }
        if ($data) {
            if ($data instanceof DimensionSingle) {
//                $form->add($this->factory->createNamed(
//                        'dimension', new DimensionSingleType(), null,
//                        array(
//                        'data_class' => 'Mapbender\WmsBundle\Component\DimensionInterval',
//                        "required" => false,
//                        'auto_initialize' => false)));
            } elseif ($data instanceof DimensionInterval) {
//                $form->add($this->factory->createNamed(
//                        'extent', new DimensionIntervalType(), null,
//                        array(
//                        'data_class' => 'Mapbender\WmsBundle\Component\DimensionInterval',
//                        'start' => $extent[0],
//                        'end' => $extent[1],
//                        'interval' => $extent[2],
//                        "required" => false,
//                        'auto_initialize' => false)));
//                $data->setExtent($data->extentStr());
//                $extent = $data->getExtent();
//                $form->add($this->factory->createNamed(
//                        'dimension', new DimensionIntervalType(), null,
//                        array(
//                        'data_class' => 'Mapbender\WmsBundle\Component\DimensionInterval',
//                        'start' => $extent[0],
//                        'end' => $extent[1],
//                        'interval' => $extent[2],
//                        "required" => false,
//                        'auto_initialize' => false)));
            } elseif ($data instanceof DimensionMultiple) {
//                $origExtent = $data->getOrigExtent();
//                $choices = array_combine($origExtent, $origExtent);
//                $form->add($this->factory->createNamed(
//                        'dimension', new DimensionMultipleType(), null,
//                        array(
//                        'data_class' => 'Mapbender\WmsBundle\Component\DimensionMultiple',
//                        'origExtent' => $choices,
//                        'auto_initialize' => false)));
            } else {
                # TODO not supported yet
            }
        }
    }

}
