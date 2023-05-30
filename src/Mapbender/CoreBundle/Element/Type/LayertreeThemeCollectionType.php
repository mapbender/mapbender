<?php

namespace Mapbender\CoreBundle\Element\Type;

use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Utils\ApplicationUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LayertreeThemeCollectionType extends AbstractType implements EventSubscriberInterface
{
    public function getParent()
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'entry_type' => 'Mapbender\CoreBundle\Element\Type\LayerThemeType',
        ));
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
    {
        return array(
            // Run before collection ResizeFormListener preSetData
            /** @see \Symfony\Component\Form\Extension\Core\Type\CollectionType::buildForm */
            /** @see \Symfony\Component\Form\Extension\Core\EventListener\ResizeFormListener::getSubscribedEvents */
            FormEvents::PRE_SET_DATA => ['preSetData', 1],
        );
    }

    public function preSetData(FormEvent $event)
    {
        $form = $event->getForm();
        /** @var Element $element */
        $element = $form->getParent()->getParent()->getData();
        $themesData = $this->mergeData($element, $event->getData());
        $event->setData($themesData);
        if (!$themesData && !$event->getForm()->isRoot()) {
            $event->getForm()->getParent()->remove($event->getForm()->getName());
        }
    }

    protected function mergeData(Element $element, $data)
    {
        $settingsMap = array();
        foreach ($data ?: array() as $themeSettings) {
            if (!empty($themeSettings['id'])) {
                $settingsMap[$themeSettings['id']] = $themeSettings;
            }
        }
        $defaults = array(
            'opened' => false,
            'useTheme' => true,
        );
        $dataOut = array();
        foreach (ApplicationUtil::getMapLayersets($element->getApplication()) as $layerset) {
            if (!empty($settingsMap[$layerset->getId()])) {
                $dataOut[] = \array_replace($settingsMap[$layerset->getId()], array(
                    'id' => $layerset->getId(),     // NOTE: may change type int <=> string
                    'title' => $layerset->getTitle(),
                ));
            } else {
                $dataOut[] = \array_replace($defaults, array(
                    'id' => $layerset->getId(),
                    'title' => $layerset->getTitle(),
                ));
            }
        }
        return $dataOut;
    }
}
