<?php

namespace Mapbender\CoreBundle\Element\EventListener;

use Mapbender\CoreBundle\Element\Type\LayerThemeType;
use Mapbender\CoreBundle\Entity\Application;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

/**
 *
 */
class LayertreeSubscriber implements EventSubscriberInterface
{
    /** @var Application */
    private $application;

    /**
     * @param Application $application
     */
    public function __construct($application)
    {
        $this->application = $application;
    }

    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
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
        $themesAll = isset($data["target"]) && $data["target"] !== null ? $this->getThemes($data) : array();
        $themesData = $this->checkDataThemes(
            $themesAll,
            isset($data["themes"]) && count($data["themes"]) > 0 ? $data["themes"] : array()
        );
        $data["themes"] = $themesAll;
        $event->setData($data);
        if ($themesData) {
            $form->add('themes', 'collection', array(
                'label' => 'mb.core.admin.layertree.label.themes',
                'data' => $themesData,
                'required' => false,
                'type' => new LayerThemeType(),
                'auto_initialize' => false,
                'label_attr' => array(
                    'class' => 'left',
                ),
            ));
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
                $sets = array_key_exists('layersets', $config) ? $config['layersets'] : array($config['layerset']);
                if (in_array($layerset->getId(), $sets)) {
                    $themes[] = array(
                        'id' => $layerset->getId(),
                        'opened' => false,
                        'title' => $layerset->getTitle(),
                        'useTheme' => true,
                        'sourceVisibility' => false,
                        'allSelected' => false);
                }
            }
        }
        return $themes;
    }

    private function checkDataThemes($themesAll, $themesData)
    {
        $themes = array();
        if (count($themesAll) === 0 || count($themesData) === 0) {
            return $themes;
        } else {
            for ($i = 0; $i < count($themesAll); $i++) {
                $found = false;
                for ($j = 0; $j < count($themesData); $j++) {
                    if (strval($themesAll[$i]['id']) === strval($themesData[$j]['id'])) {
                        $arr = $themesData[$j];
                        $arr['title'] = $themesAll[$i]['title'];
                        $themes[] = $arr;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $themes[] = $themesAll[$i];
                }
            }
        }
        return $themes;
    }
}
