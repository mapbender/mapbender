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
        $this->addExtentFields($form, $data);

        switch ($data->getType()) {
            case DimensionInst::TYPE_MULTIPLE:
                $extentArray = $data->getData($data->getExtent());
                $origExtentArray = $data->getData($data->getOrigextent());
                $choices = array_combine($origExtentArray, $origExtentArray);
                $form->add('extentEdit', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                    'data' => $extentArray,
                    'mapped' => false,
                    'choices' => $choices,
                    'choices_as_values' => true,
                    'label' => 'Extent',
                    'auto_initialize' => false,
                    'multiple' => true,
                    'required' => true,
                ));
                $form->add('default', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                    'choices' => $choices,
                    'choices_as_values' => true,
                    'auto_initialize' => false,
                ));
                break;
            case DimensionInst::TYPE_SINGLE:
            case DimensionInst::TYPE_INTERVAL:
                $form->add('extentEdit', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                    'label' => 'Extent',
                    'required' => true,
                    'auto_initialize' => false,
                ));
                break;
            default:
                break;
        }
        if ($data->getType() === DimensionInst::TYPE_INTERVAL) {
            $form->add('default', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                'auto_initialize' => false,
                'required' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
            ));
        }
    }

    /**
     * @param FormInterface $form
     * @param DimensionInst $data
     */
    protected function addExtentFields($form, $data)
    {
        $dimJs = $data->getConfiguration();
        $form
            ->add('json', 'Symfony\Component\Form\Extension\Core\Type\HiddenType', array(
                'required' => true,
                'data' => json_encode($dimJs),
                'auto_initialize' => false,
            ))
        ;
    }
}
