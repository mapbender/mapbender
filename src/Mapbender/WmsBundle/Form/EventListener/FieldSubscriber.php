<?php

namespace Mapbender\WmsBundle\Form\EventListener;

use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * FieldSubscriber class
 */
class FieldSubscriber implements EventSubscriberInterface
{
    
    /**
     * A FieldSubscriber's Factory
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
     * @param \Symfony\Component\Form\Event\DataEvent $event
     * @return type
     */
    public function preSetData(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if(null === $data)
        {
            return;
        }

        if($data->getWmslayersource()->getQueryable() === true)
        {
            $form->remove('info');
            $form->add($this->factory->createNamed(
                            'info', 'checkbox', null,
                            array(
                        'disabled' => false,
                        "required" => false)));
            $form->remove('allowinfo');
            $form->add($this->factory->createNamed(
                            'allowinfo', 'checkbox', null,
                            array(
                        'disabled' => false,
                        "required" => false)));
        }
        $arrStyles = $data->getWmslayersource()->getStyles();
        $styleOpt = array("" => "");
        foreach($arrStyles as $style)
        {
            $styleOpt[$style->getName()] = $style->getTitle();
        }

        $form->remove('style');
        $form->add($this->factory->createNamed(
                        'style', 'choice', null,
                        array(
                    'label' => 'style',
                    'choices' => $styleOpt,
                    "required" => false)));
    }

}