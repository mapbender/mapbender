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
        $ranges = explode(',', $data->getOrigextent());
        $multipleRanges = count($ranges) > 1;
        if ($multipleRanges) {
            $extentType = 'Symfony\Component\Form\Extension\Core\Type\HiddenType';
        } else {
            $extentType = 'Symfony\Component\Form\Extension\Core\Type\TextType';
        }
        $form
            ->add('extent', $extentType, array(
                'required' => true,
                'auto_initialize' => false,
                'attr' => array(
                    'readonly' => 'readonly',
                ),
                'label' => 'Extent',
            ))
        ;
        if ($multipleRanges) {
            $choices = array_combine($ranges, $ranges);
            $form->add('extentRanges', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'data' => explode(',', $data->getExtent()),
                'mapped' => false,
                'choices' => $choices,
                'choices_as_values' => true,
                'label' => $form->get('extent')->getConfig()->getOption('label'),
                'auto_initialize' => false,
                'multiple' => true,
                'required' => true,
            ));
            $form->add('default', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
                'choices' => $choices,
                'choices_as_values' => true,
                'auto_initialize' => false,
            ));
        } else {
            if (count($ranges) > 0 && count(explode('/', $ranges[0])) > 1) {
                $form->add('default', 'Symfony\Component\Form\Extension\Core\Type\TextType', array(
                    'auto_initialize' => false,
                    'required' => false,
                    'attr' => array(
                        'readonly' => 'readonly',
                    ),
                ));
            }
        }
    }
}
