<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\CoreBundle\Entity\Element;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;

/**
 * Service for element configuration and acl forms.
 *
 * Default instance in container as mapbender.manager.element_form_factory.service
 */
class ElementFormFactory
{
    /** @var ElementFilter */
    protected $elementFilter;
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var bool */
    protected $strict;
    /** @var FormRegistryInterface */
    protected $formRegistry;

    /**
     * @param ElementFilter $elementFilter
     * @param FormFactoryInterface $formFactory
     * @param FormRegistryInterface $formRegistry
     * @param bool $strict
     */
    public function __construct(ElementFilter $elementFilter,
                                FormFactoryInterface $formFactory,
                                FormRegistryInterface $formRegistry,
                                $strict = false)
    {
        $this->elementFilter = $elementFilter;
        $this->formFactory = $formFactory;
        $this->setStrict($strict);
        $this->formRegistry = $formRegistry;
    }

    public function setStrict($enable)
    {
        $this->strict = !!$enable;
    }

    /**
     * @param Element $element
     * @return array
     */
    public function getConfigurationForm($element)
    {
        // Add class and element id data attributes for functional test support
        $options = array(
            'attr' => array(
                'class' => '-ft-element-form form-horizontal',
            ),
        );
        if ($element->getId()) {
            $options['attr']['id'] = 'element-' . $element->getId();
            $options['attr']['data-ft-element-id'] = $element->getId();
        } else {
            $options['attr']['id'] = 'element-new';
        }

        if (!$element->getClass()) {
            throw new \LogicException("Empty component class name on element");
        }

        $this->elementFilter->prepareForForm($element);
        $handlingClass = $this->elementFilter->getHandlingClassName($element);

        $formType = $this->formFactory->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', $element, $options);
        $formType
            ->add('title', 'Mapbender\ManagerBundle\Form\Type\ElementTitleType', array(
                'element_class' => $handlingClass,
                'required' => false,
            ))
        ;
        $configurationType = $this->getConfigurationFormType($element);

        $options = array();

        $twigTemplate = $handlingClass::getFormTemplate();
        $options['label'] = false;

        $resolvedType = $this->formRegistry->getType($configurationType);
        if ($resolvedType->getOptionsResolver()->isDefined('application')) {
            // Only pass the "application" option if the form type requires / declares it.
            $options['application'] = $element->getApplication();
        }

        $formType->add('configuration', $configurationType, $options);

        $regionName = $element->getRegion();
        if (\is_a($handlingClass, 'Mapbender\CoreBundle\Component\ElementBase\FloatableElement', true)) {
            if (!$regionName || false !== strpos($regionName, 'content')) {
                $formType->get('configuration')->add('anchor', 'Mapbender\ManagerBundle\Form\Type\Element\FloatingAnchorType');
            } else {
                $formType->get('configuration')->add('anchor', 'Symfony\Component\Form\Extension\Core\Type\HiddenType');
            }
        }

        return array(
            'form' => $formType->getForm(),
            'theme' => $twigTemplate,
        );
    }

    protected function deprecated($message)
    {
        if ($this->strict) {
            throw new \RuntimeException($message);
        } else {
            @trigger_error("Deprecated: {$message}", E_USER_DEPRECATED);
        }
    }

    /**
     * @param Element $element
     * @return string|null
     * @throws \RuntimeException
     */
    public function getConfigurationFormType(Element $element)
    {
        $handlingClass = $this->elementFilter->getHandlingClassName($element);
        $typeName = $handlingClass::getType();
        if (is_string($typeName) && preg_match('#^[\w\d]+(\\\\[\w\d]+)+$#', $typeName)) {
            // typename is a fully qualified class name, which is good (forward compatible with Symfony 3)
            if (!is_a($typeName, 'Symfony\Component\Form\FormTypeInterface', true)) {
                throw new \RuntimeException("Not a valid form type " . print_r($typeName, true));
            }
            return $typeName;
        }
        return null;
    }
}
