<?php

namespace Mappbender\CoreBundle\Form\EventListener;

use Mappbender\CoreBundle\Element\Type\SourceSetAdminType;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * BaseSourceSwitcherFieldSubscriber class
 * 
 * @author Paul Schmidt
 */
class BaseSourceSwitcherFieldSubscriber implements EventSubscriberInterface
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
     * @var application
     */
    private $application;

    /**
     * Creates a subscriber
     * 
     * @param \Symfony\Component\Form\FormFactoryInterface $factory
     */
    public function __construct(FormFactoryInterface $factory, $application)
    {
        $this->factory = $factory;
        $this->application = $application;
    }

    /**
     * Returns defined events
     * 
     * @return array events
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_BIND => 'preBind');
    }

    /**
     * Prebind a form data
     * 
     * @param \Symfony\Component\Form\Event\DataEvent $event
     * @return type
     */
    public function preBind(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();
//
//        if(null === $data)
//        {
            return;
//        }
        if(isset($data["fullscreen"]) && $data["fullscreen"])
        {
            $form->add($this->factory->createNamed('fullscreenclass', 'text', null,
                                                   array('data' => 'fullscreen')));
        }

        if($data["target"] && $this->application)
        {
            $instList = array("" => " ");
            foreach($this->application->getElements() as $element)
            {
                $element->getId();
                if($element->getId() === intval($data["target"]))
                {
                    $mapconfig = $element->getConfiguration();
                    foreach($this->application->getLayersets() as $layerset)
                    {
                        if(intval($mapconfig['layerset']) === $layerset->getId())
                        {
                            foreach($layerset->getInstances() as $instance)
                            {
                                if($instance->getEnabled())
                                    $instList[strval($instance->getId())] = $instance->getTitle();
                            }
                            break;
                        }
                    }
                    break;
                }
            }
            if(count($instList) > 0 && isset($data['mapsets']))
            {
                $form->remove('mapsets');
                $form->add($this->factory->createNamed(
                                'mapsets', "collection", null,
                                array(
                            'property_path' => '[mapsets]',    
                            'type' => new MapsetAdminType(),
                            'options' => array(
                                'sources' => $instList
                                ))));
            }
        } else
        {
            $form->remove('mapsets');
        }
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

//        if(null === $data)
//        {
            return;
//        }
        if(isset($data["fullscreen"]) && $data["fullscreen"])
        {
            $form->add($this->factory->createNamed('fullscreenclass', 'text', null,
                                                   array('data' => 'fullscreen')));
        }

        if($data["target"] && $this->application)
        {

            $instList = array("" => " ");
            foreach($this->application->getElements() as $element)
            {
                $element->getId();
                if($element->getId() === intval($data["target"]))
                {
                    $mapconfig = $element->getConfiguration();
                    foreach($this->application->getLayersets() as $layerset)
                    {
                        if(intval($mapconfig['layerset']) === $layerset->getId())
                        {
                            foreach($layerset->getInstances() as $instance)
                            {
                                if($instance->getEnabled())
                                    $instList[strval($instance->getId())] = $instance->getTitle();
                            }
                            break;
                        }
                    }
                    break;
                }
            }
            if(count($instList) > 0)
            {
                $form->add($this->factory->createNamed(
                                'mapsets', "collection", null,
                                array(
                            'property_path' => '[mapsets]',
                            'type' => new MapsetAdminType(),
                            'options' => array(
                                'sources' => $instList
                                ))));
            }
        } else
        {
            $form->remove('mapsets');
        }
    }

}