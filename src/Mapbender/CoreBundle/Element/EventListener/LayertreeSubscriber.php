<?php

namespace Mapbender\CoreBundle\Element\EventListener;

use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Utils\ApplicationUtil;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;

class LayertreeSubscriber implements EventSubscriberInterface
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            // Run before collection ResizeFormListener preSetData
            /** @see \Symfony\Component\Form\Extension\Core\Type\CollectionType::buildForm */
            /** @see \Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener::getSubscribedEvents */
            FormEvents::PRE_SET_DATA => ['preSetData', 1],
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
        /** @var Element $element */
        $element = $form->getParent()->getParent()->getData();
        $configData = $form->getParent()->getData();
        $themesAll = $this->getThemes($element->getApplication());
        $themesData = $this->checkDataThemes(
            $themesAll,
            !empty($configData["themes"]) ? $configData["themes"] : array()
        );
        $event->setData($themesData);
        if (!$themesData && !$event->getForm()->isRoot()) {
            $event->getForm()->getParent()->remove($event->getForm()->getName());
        }
    }

    private function getThemes(Application $application)
    {
        $themes = array();
        foreach (ApplicationUtil::getMapLayersets($application) as $layerset) {
            $themes[] = array(
                'id' => $layerset->getId(),
                'opened' => false,
                'title' => $layerset->getTitle(),
                'useTheme' => true,
            );
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
