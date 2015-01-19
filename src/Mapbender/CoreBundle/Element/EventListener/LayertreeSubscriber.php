<?php

namespace Mapbender\CoreBundle\Element\EventListener;

use Mapbender\CoreBundle\Element\Type\LayerThemeType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * 
 */
class LayertreeSubscriber implements EventSubscriberInterface
{

    /**
     * A FormFactoryInterface 's Factory
     * 
     * @var \Symfony\Component\Form\FormFactoryInterface 
     */
    private $factory;

    /**
     * The application
     * 
     * @var application
     */
    private $application;

    /**
     * @inheritdoc
     */
    public function __construct(FormFactoryInterface $factory, $application)
    {
        $this->factory = $factory;
        $this->application = $application;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        );
    }

    /**
     * Checkt form fields by PRE_SUBMIT FormEvent
     * 
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();
        if (null === $data) {
            return;
        }
        $form = $event->getForm();
        if (key_exists("themes", $data)) {
            $form->remove('themes');
            foreach ($data['themes'] as &$theme) {
                $theme['opened'] = isset($theme['opened']) ? (bool) ($theme['opened']) : false;
                $theme['id'] = intval($theme['id']);
            }
            $event->setData($data);
            $form->add($this->factory->createNamed('themes', 'collection', null,
                                                   array(
                        'data' => $data["themes"],
                        'required' => false,
                        'type' => new LayerThemeType(),
                        'auto_initialize' => false,
            )));
        }
    }

    /**
     * Checkt form fields by PRE_SET_DATA FormEvent
     * 
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if (null === $data) {
            return;
        }
        $form = $event->getForm();
        $themesActual = isset($data["target"]) && $data["target"] !== null ? $this->getThemes($data) : array();
        $themesData = isset($data["themes"]) && count($data["themes"]) > 0 ? $data["themes"] : array();
        $data["themes"] = $themesActual;
        $event->setData($data);
        if (count($themesData) === 0) {
            $form->add($this->factory->createNamed('themes', 'collection', null,
                                                   array(
                        'required' => false,
                        'type' => new LayerThemeType(),
                        'auto_initialize' => false,
            )));
        } else {
            $form->add($this->factory->createNamed('themes', 'collection', null,
                                                   array(
                        'data' => $themesData,
                        'required' => false,
                        'type' => new LayerThemeType(),
                        'auto_initialize' => false,
            )));
        }
    }

    private function getThemes($data)
    {
        if (!$data || !isset($data['target'])) {
            return array();
        }
        $mapEl = null;
        foreach ($this->application->getElements() as $element) {
            if (strval($element->getId()) === strval($data['target'])) {
                $mapEl = $element;
                break;
            }
        }
        $themes = array();
        if ($mapEl) {
            $config = $mapEl->getConfiguration();
            foreach ($this->application->getLayersets() as $layerset) {
                if (in_array($layerset->getId(), $config['layersets'])) {
                    $themes[] = array(
                        'id' => $layerset->getId(),
                        'opened' => false,
                        'title' => $layerset->getTitle());
                }
            }
        }
        return $themes;
    }

}
