<?php

namespace Mapbender\WmsBundle\Form\EventListener;

use Mapbender\WmsBundle\Component\DimensionInst;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;

class DimensionSubscriber implements EventSubscriberInterface
{

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
    protected function addFields($form, $data)
    {
        $form
            ->add('type', 'hidden', array(
                'auto_initialize' => false,
                'read_only' => true,
                'required' => true,
            ))
            ->add('name', 'text', array(
                'auto_initialize' => false,
                'read_only' => true,
                'required' => true,
            ))
            ->add('units', 'text', array(
                'auto_initialize' => false,
                'read_only' => true,
                'required' => false,
                'attr' => array(
                    'data-name' => 'units',
                ),
            ))
            ->add('unitSymbol', 'text', array(
                'auto_initialize' => false,
                'read_only' => true,
                'required' => false,
                'attr' => array(
                    'data-name' => 'unitSymbol',
                ),
            ))
            ->add('multipleValues', 'checkbox', array(
                'auto_initialize' => false,
                'label' => 'multiple',
                'disabled' => true,
                'required' => false,
            ))
            ->add('nearestValue', 'checkbox', array(
                'auto_initialize' => false,
                'label' => 'nearest',
                'disabled' => true,
                'required' => false,
            ))
            ->add('current', 'checkbox', array(
                'auto_initialize' => false,
                'label' => 'current',
                'disabled' => true,
                'required' => false,
            ))
        ;
        $this->addExtentFields($form, $data);

        switch ($data->getType()) {
            case DimensionInst::TYPE_MULTIPLE:
                $extentArray = $data->getData($data->getExtent());
                $origExtentArray = $data->getData($data->getOrigextent());
                $choices = array_combine($origExtentArray, $origExtentArray);
                $form->add('extentEdit', 'choice', array(
                    'data' => $extentArray,
                    'mapped' => false,
                    'choices' => $choices,
                    'choices_as_values' => true,
                    'label' => 'Extent',
                    'auto_initialize' => false,
                    'multiple' => true,
                    'required' => true,
                ));
                $form->add('default', 'choice', array(
                    'choices' => $choices,
                    'choices_as_values' => true,
                    'auto_initialize' => false,
                ));
                break;
            case DimensionInst::TYPE_SINGLE:
            case DimensionInst::TYPE_INTERVAL:
                $form->add('extentEdit', 'text', array(
                    'label' => 'Extent',
                    'required' => true,
                    'auto_initialize' => false,
                ));
                break;
            default:
                break;
        }
        if ($data->getType() === DimensionInst::TYPE_INTERVAL) {
            $form->add('default', 'text', array(
                'auto_initialize' => false,
                'read_only' => false,
                'required' => false,
            ));
        }
    }

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

        $dimJs = $data->getConfiguration();
        $form
            ->add('json', 'hidden', array(
                'required' => true,
                'data' => json_encode($dimJs),
                'auto_initialize' => false,
            ))
        ;
    }
}
