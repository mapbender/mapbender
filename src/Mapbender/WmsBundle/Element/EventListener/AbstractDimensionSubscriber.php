<?php


namespace Mapbender\WmsBundle\Element\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\Form\FormInterface;


abstract class AbstractDimensionSubscriber implements EventSubscriberInterface
{

    /**
     * A DimensionSubscriber's Factory
     *
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    protected $factory;

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
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }
        $this->addFields($form, $data);
    }

    /**
     * @param FormInterface $form
     * @param DimensionInst $data
     */
    abstract protected function addFields($form, $data);

    /**
     * @param FormInterface $form
     * @param DimensionInst $data
     */
    protected function addExtentFields($form, $data)
    {
        $form
            ->add('extent', 'hidden', array(
                'required' => true,
                'auto_initialize' => false,
                'attr' => array(
                    'data-extent' => 'group-dimension-extent',
                    'data-name' => 'extent',
                ),
            ))
            ->add('origextent', 'hidden', array(
                'required' => true,
                'auto_initialize' => false,
                'mapped' => false,
                'attr' => array(
                    'data-extent' => 'group-dimension-origextent',
                    'data-name' => 'origextent',
                ),
            ))
        ;
    }
}
