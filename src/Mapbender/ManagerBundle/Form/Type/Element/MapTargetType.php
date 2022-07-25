<?php


namespace Mapbender\ManagerBundle\Form\Type\Element;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Utils\ApplicationUtil;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\DataTransformer\IntegerToLocalizedStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Selects a Map element for use in an Elements configuration form.
 * Model data is the Map Element's id.
 * Finds the map element by traversing the Element collection
 * => does not require any services to be injected.
 * If there is only a single valid choice, the form field will be
 * hidden and automatically initialized.
 *
 * Assumes form has two parents (configuration array => Element entity).
 */
class MapTargetType extends AbstractType implements EventSubscriberInterface
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::PRE_SET_DATA => 'preSetData',
        );
    }

    public function preSetData(FormEvent $event)
    {
        $name = $event->getForm()->getConfig()->getName();
        /** @var Element $element */
        $element = $event->getForm()->getParent()->getParent()->getData();
        $mapElements = array();
        foreach (ApplicationUtil::getMapElements($element->getApplication()) as $mapElement) {
            $mapElements[$mapElement->getId()] = $mapElement;
        }
        $mapIds = array_keys($mapElements);
        $parentForm = $event->getForm()->getParent();
        // @todo: with no maps, a required map target should be an error
        if (count($mapElements) !== 1) {
            $this->addChoice($name, $parentForm, $mapElements, $event->getForm()->getConfig()->getOptions());
        } else {
            $this->addHidden($name, $parentForm);
        }

        // Auto-initialize / replace invalid target ids
        $elementConfig = $element->getConfiguration();
        if ($mapElements && (empty($elementConfig[$name]) || empty($mapElements[$elementConfig[$name]]))) {
            $parentForm->get($name)->setData(\intval($mapIds[0]));
            // Setting same data again on different scope to support other event listeners
            $event->setData(\intval($mapIds[0]));
        } elseif ($event->getData() !== null) {
            if ($mapIds) {
                $parentForm->get($name)->setData(\intval($mapIds[0]));
            }
            // Setting same data again on different scope to support other event listeners
            $event->setData(\intval($event->getData()));
        }
    }

    protected function addChoice($name, FormInterface $target, $mapElements, array $options)
    {
        $target->add($name, 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', array(
            'choices' => array_keys($mapElements),
            'choice_label' => function($mapId) use ($mapElements) {
                /** @var Element $mapElement */
                $mapElement = $mapElements[$mapId];
                /** @var MinimalInterface|string $className */
                $className = $mapElements[$mapId]->getClass();
                return $mapElement->getTitle() ?: $className::getClassTitle();
            },
            'choice_value' => function($mapId) {
                return $mapId !== null ? \intval($mapId) : $mapId;
            },
            'label' => $options['label'],
            'required' => $options['required'],
        ));
    }

    protected function addHidden($name, FormInterface $target)
    {
        $child = $target->getConfig()->getFormFactory()->createNamedBuilder($name, 'Symfony\Component\Form\Extension\Core\Type\HiddenType', null, array(
            'auto_initialize' => false,
            'label' => false,
        ));
        $child->addModelTransformer(new IntegerToLocalizedStringTransformer());
        $target->add($child->getForm(), 'Symfony\Component\Form\Extension\Core\Type\HiddenType');
    }
}
