<?php


namespace Mapbender\ManagerBundle\Form\Type\Element;


use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
    public function __construct(protected TranslatorInterface $translator, protected ElementFilter $elementFilter)
    {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'element_filter_function' => null,
            'region_name_pattern' => function(Options $options): ?string {
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
            'placeholder' => fn(Options $options): ?string => $options['required'] ? null : '',
        ]);
        $resolver->setAllowedTypes('element_filter_function', ['null', 'callable']);
        $resolver->setAllowedTypes('include_buttons', ['bool']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventSubscriber($this);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSetData',
        ];
    }

    public function preSetData(FormEvent $event): void
    {
        $element = $event->getForm()->getParent()->getParent()->getData();
        $config = $event->getForm()->getConfig();
        $options = $config->getOptions();
        $elements = $this->getTargets($element, $options);
        // REPLACE entire type with a ChoiceType
        $name = $event->getForm()->getName();
        $choiceOptions = [
            'choices' => $this->formatChoices($elements),
            'choice_value' => function($choice): ?int {
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
        ];
        $parentForm = $event->getForm()->getParent();
        $parentForm->add($name, ChoiceType::class, $choiceOptions);
    }

    /**
     * @param Element $element
     * @param array $options
     * @return Element[]
     */
    protected function getTargets(Element $element, array $options): array
    {
        $elementMap = [];
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
        $choices = [];
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
    protected function getFilterFunction(array $options)
    {
        $baseFilter = function(Element $element) use ($options): bool {
            $className = $element->getClass();
            if (!$className || !ClassUtil::exists($className)) {
                return false;
            }
            if (\is_a($className, 'Mapbender\Component\Element\MainMapElementInterface', true)) {
                return false;
            }
            if (!$options['include_buttons']) {
                // Service-type ButtonLike
                if (\is_a($className, 'Mapbender\Component\Element\ButtonLike', true)) {
                    return false;
                }
            }
            if ($options['region_name_pattern'] && !preg_match($options['region_name_pattern'], (string) $element->getRegion())) {
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
            return fn(Element $element): bool => $baseFilter($element) && ($options['element_filter_function']($element));
        } else {
            return $baseFilter;
        }
    }

    /**
     * @param array $choices
     * @return array
     */
    protected function sortChoices(array $choices): array
    {
        $titles = [];
        foreach (array_keys($choices) as $title) {
            $titles[] = $this->translator->trans($title);
        }
        $choices = [] + $choices;
        \array_multisort($titles, $choices);
        return $choices;
    }
}
