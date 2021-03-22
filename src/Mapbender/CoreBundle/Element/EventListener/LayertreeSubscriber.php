<?php

namespace Mapbender\CoreBundle\Element\EventListener;

use Doctrine\Common\Collections\Criteria;
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
        $form = $event->getForm();
        $themesAll = $this->getThemes($event->getForm()->get('target')->getData());
        $themesData = $this->checkDataThemes(
            $themesAll,
            isset($data["themes"]) && count($data["themes"]) > 0 ? $data["themes"] : array()
        );
        $data["themes"] = $themesAll;
        $event->setData($data);
        if ($themesData) {
            $form->add('themes', 'Symfony\Component\Form\Extension\Core\Type\CollectionType', array(
                'label' => 'mb.core.admin.layertree.label.themes',
                'data' => $themesData,
                'required' => false,
                'entry_type' => 'Mapbender\CoreBundle\Element\Type\LayerThemeType',
            ));
        }
    }

    private function getThemes($mapId)
    {
        if (!$mapId) {
            return array();
        }
        $criteria = Criteria::create()->where(Criteria::expr()->eq('id', $mapId));
        $mapEl = $this->application->getElements()->matching($criteria)->first();
        if (!$mapEl) {
            return array();
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
                    );
                }
            }
        }
        return $themes;
    }

    private function checkDataThemes($themesAll, $themesData)
    {
        $themes = array();
        foreach ($themesAll as $availableTheme) {
            $found = false;
            foreach ($themesData as $configuredTheme) {
                if (strval($availableTheme['id']) === strval($configuredTheme['id'])) {
                    $themes[] = array_replace($configuredTheme, array(
                        'title' => $availableTheme['title'],
                    ));
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $themes[] = $availableTheme;
            }
        }
        return $themes;
    }
}
