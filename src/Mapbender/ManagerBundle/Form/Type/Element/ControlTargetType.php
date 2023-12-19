<?php


namespace Mapbender\ManagerBundle\Form\Type\Element;


use Mapbender\Component\ClassUtil;
use Mapbender\CoreBundle\Component\ElementBase\FloatingElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Choice of targettable non-map Elements. Model data is the target's id.
 *
 * By default excludes floatables (ZoomBar etc).
 * Use include_floatable option to include.
 *
 * By default excludes any Button-likes.
 * Use include_buttons option to include.
 *
 * By default offers only targets inside regions usually containing popup-style elements.
 * Use region_name_pattern option (PHP regex) to disable / adjust region filtering behaviour.
 *
 * Optionally accepts an element_filter_function (Closure receiving Element entity as an argument,
 * returning boolean) to layer additional filtering.
 */
class ControlTargetType extends AbstractType implements EventSubscriberInterface
{
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var TranslatorInterface */
    protected $translator;

    public function __construct(TranslatorInterface $translator,
                                ElementFilter $elementFilter)
    {
        $this->translator = $translator;
        $this->elementFilter = $elementFilter;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'element_filter_function' => null,
            'region_name_pattern' => function(Options $options) {
                if ($options['include_buttons']) {
                    return null;
                } else {
                    return '#(content|mobilePane)#';
                }
            },
            'include_buttons' => false,
            'include_floatable' => false,
            // placeholder = same as ChoiceType
            /* @see \Symfony\Component\Form\Extension\Core\Type\ChoiceType::configureOptions() */
            'placeholder' => function (Options $options) {
                return $options['required'] ? null : '';
            },
        ));
        $resolver->setAllowedTypes('element_filter_function', array('null', 'callable'));
        $resolver->setAllowedTypes('include_buttons', array('bool'));
    }

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
        $element = $event->getForm()->getParent()->getParent()->getData();
        $config = $event->getForm()->getConfig();
        $options = $config->getOptions();
        $elements = $this->getTargets($element, $options);
        // REPLACE entire type with a ChoiceType
        $name = $event->getForm()->getName();
        $choiceOptions = array(
            'choices' => $this->formatChoices($elements),
            'choice_value' => function($choice) {
                if ($choice) {
                    return \intval($choice);
                } else {
                    return null;
                }
            },
            'label' => $config->getOption('label'),
            'placeholder' => $config->getOption('placeholder'),
            'required' => $config->getOption('required'),
            'constraints' => $config->getOption('constraints'),
        );
        $parentForm = $event->getForm()->getParent();
        $parentForm->add($name, 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', $choiceOptions);
    }

    /**
     * @param Element $element
     * @param array $options
     * @return Element[]
     */
    protected function getTargets(Element $element, array $options)
    {
        $elementMap = array();
        $elements = $element->getApplication()->getElements();
        $filterFunction = $this->getFilterFunction($options);
        foreach ($elements as $other) {
            if ($other !== $element && $filterFunction($other)) {
                $elementMap[$other->getId()] = $other;
            }
        }
        return $elementMap;
    }

    /**
     * @param Element[] $elements
     * @return array
     */
    protected function formatChoices($elements)
    {
        $choices = array();
        foreach ($elements as $element) {
            $title = $element->getTitle() ?: $this->elementFilter->getDefaultTitle($element);
            $choices[$title] = $element->getId();
        }
        return $this->sortChoices($choices);
    }

    /**
     * @param array $options
     * @return \Closure
     */
    protected function getFilterFunction($options)
    {
        $baseFilter = function(Element $element) use ($options) {
            $className = $element->getClass();
            if (!$className || !ClassUtil::exists($className)) {
                return false;
            }
            if (\is_a($className, 'Mapbender\Component\Element\MainMapElementInterface', true)) {
                return false;
            }
            if (!$options['include_buttons']) {
                // Legacy Component\Element style
                if (\is_a($className, 'Mapbender\CoreBundle\Element\BaseButton', true)) {
                    return false;
                }
                // Service-type ButtonLike
                if (\is_a($className, 'Mapbender\Component\Element\ButtonLike', true)) {
                    return false;
                }
            }
            if ($options['region_name_pattern'] && !preg_match($options['region_name_pattern'], $element->getRegion())) {
                return false;
            }
            if (!$options['include_floatable'] && \is_a($className, FloatingElement::class, true)) {
                return false;
            }
            $r = new \ReflectionClass($className);
            if ($r->hasProperty('ext_api') && $r->getProperty('ext_api')->isStatic()) {
                if (!$r->getStaticPropertyValue('ext_api')) {
                    return false;
                }
            }
            return true;
        };
        if (!empty($options['element_filter_function'])) {
            return function(Element $element) use ($baseFilter, $options) {
                return $baseFilter($element) && ($options['element_filter_function']($element));
            };
        } else {
            return $baseFilter;
        }
    }

    /**
     * @param array $choices
     * @return array
     */
    protected function sortChoices(array $choices)
    {
        $titles = array();
        foreach (array_keys($choices) as $title) {
            $titles[] = $this->translator->trans($title);
        }
        $choices = array() + $choices;
        \array_multisort($titles, $choices);
        return $choices;
    }
}
