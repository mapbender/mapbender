<?php

namespace Mapbender\WmsBundle\Element\EventListener;

use Mapbender\WmsBundle\Component\DimensionInst;
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
        return array(FormEvents::PRE_SET_DATA => 'preSetData');
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
        if ($data && $data instanceof DimensionInst) {
            $this->addFields($form, $data, $event);
        }
    }

    private function addFields($form, $data, $event)
    {
        $data->setExtent(json_encode($data->getData($data->getExtent())));
        $data->setOrigextent(json_encode($data->getData($data->getOrigextent())));
        $form
            ->add($this->factory->createNamed('extent', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-extent' => 'group-dimension-extent', 'data-name' => 'extent'))))
            ->add($this->factory->createNamed('name', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'name'))))
            ->add($this->factory->createNamed('units', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'units'))))
            ->add($this->factory->createNamed('unitSymbol', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'unitSymbol'))))
            ->add($this->factory->createNamed('default', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'default'))))
            ->add($this->factory->createNamed('multipleValues', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'multipleValues'))))
            ->add($this->factory->createNamed('nearestValue', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'nearestValue'))))
            ->add($this->factory->createNamed('current', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'current'))))
            ->add($this->factory->createNamed('type', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'type'))))
            ->add($this->factory->createNamed('active', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'active'))))
            ->add($this->factory->createNamed('origextent', 'hidden', null,
                    array('auto_initialize' => false, 'attr' => array('data-name' => 'origextent'))))
        ;
    }

}
