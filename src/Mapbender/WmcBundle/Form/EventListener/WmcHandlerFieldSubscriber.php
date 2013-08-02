<?php

namespace Mapbender\WmcBundle\Form\EventListener;

use Mapbender\CoreBundle\Entity\State;
use Symfony\Component\Form\Event\DataEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * 
 */
class WmcHandlerFieldSubscriber implements EventSubscriberInterface
{

    protected $factory;
    /**
     * @inheritdoc
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
        return array(FormEvents::PRE_BIND => 'preBind');
    }

    /**
     * Checkt form fields by PRE_BIND DataEvent
     * 
     * @param DataEvent $event
     */
    public function preBind(DataEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

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
        if(!key_exists("accessGroupsLoader", $data))
        {
            $form->remove('accessGroupsLoader');
            $data_ = $form->getData();
            $data_['accessGroupsLoader'] = new ArrayCollection();
            $form->setData($data_);
            $form->add($this->factory->createNamed(
                            'accessGroupsLoader', 'fom_groups', null,
                            array(
                    'return_entity' => false,
                    'user_groups'   => false,
                    'property_path' => '[accessGroupsLoader]',
                    'required'      => false,
                    'multiple'      => true,
                    'empty_value' => 'Choose an option',
                            )));
        }
        if(!key_exists("accessGroupsEditor", $data))
        {
            $form->remove('accessGroupsEditor');
            $data_ = $form->getData();
            $data_['accessGroupsEditor'] = new ArrayCollection();
            $form->setData($data_);
            $form->add($this->factory->createNamed(
                            'accessGroupsEditor', 'fom_groups', null,
                            array(
                    'return_entity' => false,
                    'user_groups'   => false,
                    'property_path' => '[accessGroupsEditor]',
                    'required'      => false,
                    'multiple'      => true,
                    'empty_value' => 'Choose an option',
                            )));
        }
    }
}