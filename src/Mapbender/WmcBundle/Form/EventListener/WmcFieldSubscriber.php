<?php
//
//namespace Mapbender\WmcBundle\Form\EventListener;
//
//use Mapbender\CoreBundle\Entity\State;
//use Symfony\Component\Form\Event\DataEvent;
//use Symfony\Component\Form\FormFactoryInterface;
//use Symfony\Component\EventDispatcher\EventSubscriberInterface;
//use Symfony\Component\Form\FormEvents;
//use Doctrine\Common\Collections\ArrayCollection;
//
///**
// * 
// */
//class WmcFieldSubscriber implements EventSubscriberInterface
//{
//
//    protected $factory;
//    /**
//     * @inheritdoc
//     */
//    public function __construct(FormFactoryInterface $factory)
//    {
//        $this->factory = $factory;
//    }
//
//    /**
//     * @inheritdoc
//     */
//    public static function getSubscribedEvents()
//    {
//        return array(FormEvents::PRE_BIND => 'preBind');
//    }
//
//    /**
//     * Checkt form fields by PRE_BIND DataEvent
//     * 
//     * @param DataEvent $event
//     */
//    public function preBind(DataEvent $event)
//    {
//        $data = $event->getData();
//        $form = $event->getForm();
//
//        if(null === $data)
//        {
//            return;
//        }
//        if(key_exists("state", $data) && strlen($data["state"]) > 0)
//        {
//            $state = new State();
//            $state->setJson(json_decode($data["state"]));
//            $data["state"] = $state;
//            $event->setData($data);
//        }
//    }
//}