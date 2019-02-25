<?php

namespace Mapbender\CoreBundle\Form\EventListener;

use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 * MapFieldSubscriber
 */
class MapFieldSubscriber implements EventSubscriberInterface
{

    /**
     * A FormFactoryInterface 's Factory
     * @var \Symfony\Component\Form\FormFactoryInterface
     */
    private $factory;

    /** @var Application */
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
            FormEvents::PRE_SUBMIT => 'preSubmit',);
    }

    /**
     * Checkt form fields by PRE_SUBMIT FormEvent
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (null === $data) {
            return;
        }
        if (key_exists("otherSrs", $data) && is_string($data["otherSrs"])) {
            $data["otherSrs"] = preg_split("/\s?,\s?/", $data["otherSrs"]);
            $event->setData($data);
        }
        if (key_exists("scales", $data) && is_string($data["scales"])) {
            $scales = preg_split("/\s?[\,\;]+\s?/", $data["scales"]);
            arsort($scales, SORT_NUMERIC);
            $data["scales"] = $scales;
            $event->setData($data);
        }
        $form = $event->getForm();
        if (key_exists("layersets", $data) && is_array($data["layersets"])) {
            $form->remove('layersets');
            $event->setData($data);
            $choices = $this->getChoicesLayersets($data['layersets']);
            $form->add($this->factory->createNamed('layersets', 'choice', null, array(
                'choices' => $choices,
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'data' => $data["layersets"],
                'auto_initialize' => false,
                'attr' => array('data-sortable' => 'choiceExpandedSortable'))));
            $event->setData($data);
        }
    }

    /**
     * Checkt form fields by PRE_SET_DATA FormEvent
     * @param FormEvent $event
     */
    public function preSetData(FormEvent $event)
    {
        $data = $event->getData();
        if (null === $data) {
            return;
        }
        $form = $event->getForm();

        if (key_exists("otherSrs", $data) && is_array($data["otherSrs"])) {
            $data["otherSrs"] = implode(",", $data["otherSrs"]);
            $event->setData($data);
        }
        if (key_exists("scales", $data) && is_array($data["scales"])) {
            $data["scales"] = implode(",", $data["scales"]);
            $event->setData($data);
        }
        if (key_exists("layerset", $data) && !key_exists("layersets", $data)) {# "layerset" deprecated start
            $data["layersets"] = array($data["layerset"]);
            $event->setData($data);
        } # "layerset" deprecated end
        if (key_exists("layersets", $data) && is_array($data["layersets"])) {
            $form->add($this->factory->createNamed('layersets', 'choice', null, array(
                'choices' => $this->getChoicesLayersets($data['layersets']),
                'required' => true,
                'multiple' => true,
                'expanded' => true,
                'auto_initialize' => false,
                'attr' => array('data-sortable' => 'choiceExpandedSortable'))));
        }
    }

    private function getChoicesLayersets(array $selected = array())
    {
        $layersets = array();
        foreach ($this->application->getLayersets() as $layerset) {
            $layersets[$layerset->getId()] = $layerset->getTitle();
        }
        if (count($selected) > 0) {
            $layersets_ = array();
            foreach ($selected as $id) {
                if (isset($layersets[$id])) {
                    $layersets_[$id] = $layersets[$id];
                    unset($layersets[$id]);
                }
            }
            foreach ($layersets as $id => $title) {
                $layersets_[$id] = $title;
            }
            return $layersets_;
        } else {
            return $layersets;
        }
    }
}
