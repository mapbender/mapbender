<?php

namespace Mapbender\WmtsBundle\Form\EventListener;

use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * FieldSubscriber class
 */
class FieldSubscriber implements EventSubscriberInterface
{

    /**
     * Returns defined events
     * @return array events
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SET_DATA => 'preSubmit',
        );
    }

    public function preSetData(FormEvent $event)
    {
        if ($event->getData()) {
            $this->reconfigureFields($event->getForm(), $event->getData());
        }
    }

    public function preSubmit(FormEvent $event)
    {
        if ($event->getData()) {
            $this->reconfigureFields($event->getForm(), $event->getData());
        }
    }

    protected function reconfigureFields(FormInterface $form, WmtsInstanceLayer $data)
    {
        $form
            ->add('tileMatrixSet', 'Mapbender\WmtsBundle\Form\Type\WmtsInstanceLayerMatrixSetType', array(
                'instance_layer' => $data,
            ))
        ;

        if (count($data->getSourceItem()->getInfoformats())) {
            $form->remove('info');
            $form->add('info', 'checkbox', array(
                'disabled' => false,
                "required" => false,
                'auto_initialize' => false,
            ));
            $form->remove('allowinfo');
            $form->add('allowinfo', 'checkbox', array(
                'disabled' => false,
                "required" => false,
                'auto_initialize' => false,
            ));
        }
        $arrStyles = $data->getSourceItem()->getStyles();
        $styleOpt = array("" => "");
        foreach ($arrStyles as $style) {
            $styleOpt[$style->getIdentifier()] = $style->getTitle();
        }
        $form->add('style', 'choice', array(
            'choices' => $styleOpt,
            "required" => false,
            'auto_initialize' => false
        ));
        $infoFormats = $data->getSourceItem()->getInfoformats();
        $infoFormatOpt = array();
        foreach ($infoFormats as $infoFromat) {
            $infoFormatOpt[$infoFromat] = $infoFromat;
        }
        $form->add('infoformat', 'choice', array(
            'choices' => $infoFormatOpt,
            "required" => false,
            'auto_initialize' => false
        ));
    }
}
