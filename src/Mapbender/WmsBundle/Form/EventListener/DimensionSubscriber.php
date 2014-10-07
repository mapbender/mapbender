<?php

namespace Mapbender\WmsBundle\Form\EventListener;

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
//            $type = $data['type'];
//            if ($type === DimensionInst::TYPE_INTERVAL && isset($data['extent']) && is_array($data['extent'])) {
//                $data['extent'] = implode("/", $data['extent']);
//                $event->setData($data);
//            } else
//            if ($type === DimensionInst::TYPE_MULTIPLE && isset($data['extent']) && is_array($data['extent'])) {
//                $data['extent'] = implode(",", $data['extent']);
//                $event->setData($data);
//            }
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
        if ($data && $data instanceof DimensionInst) {
            $this->addFields($form, $data, $event);
        }
    }

    private function addFields($form, $data, $event)
    {
        $isVordefined = $data->getOrigextent() !== null;
        $form->add($this->factory->createNamed('creator', 'hidden', null,
                    array(
                    'auto_initialize' => false,
                    'read_only' => $isVordefined,
                    'required' => true)))
            ->add($this->factory->createNamed('type', 'hidden', null,
                    array(
                    'auto_initialize' => false,
                    'read_only' => $isVordefined,
                    'required' => true)))
            ->add($this->factory->createNamed('name', 'text', null,
                    array(
                    'auto_initialize' => false,
                    'read_only' => $isVordefined,
                    'required' => true)))
            ->add($this->factory->createNamed('units', 'text', null,
                    array(
                    'auto_initialize' => false,
                    'read_only' => $isVordefined,
                    'required' => false)))
            ->add($this->factory->createNamed('unitSymbol', 'text', null,
                    array(
                    'auto_initialize' => false,
                    'read_only' => $isVordefined,
                    'required' => false)))
            ->add($this->factory->createNamed('multipleValues', 'checkbox', null,
                    array(
                    'auto_initialize' => false,
                    'disabled' => $isVordefined,
                    'required' => false)))
            ->add($this->factory->createNamed('nearestValue', 'checkbox', null,
                    array(
                    'auto_initialize' => false,
                    'disabled' => $isVordefined,
                    'required' => false)))
            ->add($this->factory->createNamed('current', 'checkbox', null,
                    array(
                    'auto_initialize' => false,
                    'disabled' => $isVordefined,
                    'required' => false)))
            ->add($this->factory->createNamed('extent', 'hidden', null,
                    array(
                    'required' => true,
                    'auto_initialize' => false)))
            ->add($this->factory->createNamed('origextent', 'hidden', null,
                    array(
                    'required' => true,
                    'auto_initialize' => false)));
        if ($isVordefined) {
            $dataArr = $data->getData($data->getExtent());
            $dataOrigArr = $data->getData($data->getOrigextent());
            if ($data->getType() === $data::TYPE_SINGLE) {
                $form->add($this->factory->createNamed('extentEdit', 'text', null,
                        array(
                        'required' => true,
                        'auto_initialize' => false)));
            } elseif ($data->getType() === $data::TYPE_MULTIPLE) {
                $choices = array_combine($dataOrigArr, $dataOrigArr);
                $form->add($this->factory->createNamed('extentEdit', 'choice', null,
                            array(
                            'data' => $dataArr,
                            'mapped' => false,
                            'choices' => $choices,
                            'auto_initialize' => false,
                            'multiple' => true,
                            'required' => true)))
                    ->add($this->factory->createNamed('default', 'choice', null,
                            array(
                            'choices' => $choices,
                            'auto_initialize' => false,)));
            } elseif ($data->getType() === $data::TYPE_INTERVAL) {
                $form->add($this->factory->createNamed('extentEdit', 'text', null,
                            array(
                            'required' => true,
                            'auto_initialize' => false)))
                    ->add($this->factory->createNamed('default', 'text', null,
                            array(
                            'auto_initialize' => false,
                            'read_only' => $isVordefined,
                            'required' => false)));
//                $form->add($this->factory->createNamed('extent', 'hidden', null,
//                            array(
//                            'required' => true,
//                            'auto_initialize' => false)))
//                    ->add($this->factory->createNamed('origextent', 'hidden', null,
//                            array(
//                            'required' => true,
//                            'auto_initialize' => false)));
            }
        }
    }

}
