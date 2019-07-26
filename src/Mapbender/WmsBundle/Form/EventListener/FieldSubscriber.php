<?php

namespace Mapbender\WmsBundle\Form\EventListener;

use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * FieldSubscriber class
 */
class FieldSubscriber implements EventSubscriberInterface
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
        /** @var WmsInstanceLayer $data */
        $data = $event->getData();
        $form = $event->getForm();

        if (null === $data) {
            return;
        }
        $form->remove('title');
        $form->add('title', 'text', array(
            'required' => false,
            'attr' => array(
                'placeholder' => $data->getSourceItem()->getTitle(),
            ),
        ));
        $hasSubLayers = !!$data->getSublayer()->count();

        $form->remove('toggle');
        $form->add('toggle', 'checkbox', array(
            'disabled' => !$hasSubLayers,
            'required' => false,
            'auto_initialize' => false,
        ));
        $form->remove('allowtoggle');
        $form->add('allowtoggle', 'checkbox', array(
            'disabled' => !$hasSubLayers,
            'required' => false,
            'auto_initialize' => false,
        ));

        if ($data->getSourceItem()->getQueryable() === true) {
            $form->remove('info');
            $form->add('info', 'checkbox', array(
                'disabled' => false,
                'required' => false,
                'auto_initialize' => false,
            ));
            $form->remove('allowinfo');
            $form->add('allowinfo', 'checkbox', array(
                'disabled' => false,
                'required' => false,
                'auto_initialize' => false,
            ));
        }
        $arrStyles = $data->getSourceItem()->getStyles(true);
        $styleOpt = array("" => " ");
        foreach ($arrStyles as $style) {
            if(strtolower($style->getName()) !== 'default'){ // accords with WMS Implementation Specification
                $styleOpt[$style->getName()] = $style->getTitle();
            }
        }

        $form->remove('style');
        $form->add('style', 'choice', array(
            'label' => 'style',
            'choices' => $styleOpt,
            "required" => false,
            'auto_initialize' => false,
        ));
    }

}
