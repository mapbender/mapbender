<?php

namespace Mapbender\WmsBundle\Form\EventListener;

use Mapbender\CoreBundle\Form\Type\InstanceLayerStyleChoiceType;
use Mapbender\WmsBundle\Entity\WmsInstanceLayer;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

class FieldSubscriber implements EventSubscriberInterface
{
    /**
     * Returns defined events
     *
     * @return array events
     */
    public static function getSubscribedEvents(): array
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

        $layer = $event->getData();
        $options = array(
            'label' => 'Style',
            'layer' => $layer,
            'required' => false,
        );
        if ($layer->getSublayer()->count() > 0) {
            $options['attr'] = [
                'readonly' => true,
                'title' => 'mb.wms.wmsloader.repo.instancelayerform.style_leafs_only'
            ];
        }

        $form->add('style', InstanceLayerStyleChoiceType::class, $options);
    }
}
