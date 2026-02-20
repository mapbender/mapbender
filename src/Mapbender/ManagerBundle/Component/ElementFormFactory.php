<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\Component\Element\AbstractElementService;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Element\Type\IconClassType;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\FrameworkBundle\Component\ElementFilter;
use Mapbender\ManagerBundle\Form\Type\Element\FloatingAnchorType;
use Mapbender\ManagerBundle\Form\Type\ElementTitleType;
use phpDocumentor\Reflection\Types\ClassString;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Service for element configuration and permission forms.
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
    public function __construct(ElementFilter         $elementFilter,
                                FormFactoryInterface  $formFactory,
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
                'class' => '-ft-element-form',
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
        /** @var class-string<AbstractElementService> $handlingClass */
        $handlingClass = $this->elementFilter->getHandlingClassName($element);

        $formType = $this->formFactory->createBuilder(FormType::class, $element, $options);
        $this->addCommonTypes($formType, $handlingClass);
        $configurationType = $this->getConfigurationFormType($element);

        $options = array();

        $twigTemplate = $handlingClass::getFormTemplate();
        $options['label'] = false;

        $resolvedType = $this->formRegistry->getType($configurationType);
        if ($resolvedType->getOptionsResolver()->isDefined('application')) {
            // Only pass the "application" option if the form type requires / declares it.
            $options['application'] = $element->getApplication();
        }

        $options = $handlingClass::getFormOptions($element, $options);

        $formType->add('configuration', $configurationType, $options);

        $regionName = $element->getRegion();
        $this->addRegionDependantConfiguration($handlingClass, $regionName, $formType);

        return array(
            'form' => $formType->getForm(),
            'theme' => $twigTemplate,
        );
    }

    protected function addCommonTypes(FormBuilderInterface $form, string $elementClass): void
    {
        $form->add('title', ElementTitleType::class, array(
            'element_class' => $elementClass,
            'label' => 'mb.core.admin.title',
            'required' => false,
        ));
    }

    protected function addRegionDependantConfiguration(string $elementClass, ?string $regionName, FormBuilderInterface $form): void
    {
        if (is_a($elementClass, FloatableElement::class, true)) {
            if (!$regionName || str_contains($regionName, 'content')) {
                $form->get('configuration')->add('anchor', FloatingAnchorType::class);
            } else {
                $form->get('configuration')->add('anchor', HiddenType::class);
            }
        }
        if ($regionName && str_contains($regionName, 'sidepane')) {
            $form->get('configuration')->add('element_icon', IconClassType::class, array(
                'required' => false,
                'label' => 'mb.core.basebutton.admin.elementIcon',
            ));
        }
    }

    /**
     * @param Element $element
     * @return string|null
     * @throws \RuntimeException
     */
    public function getConfigurationFormType(Element $element)
    {
        /** @var class-string<AbstractElementService> $handlingClass */
        $handlingClass = $this->elementFilter->getHandlingClassName($element);
        $typeName = $handlingClass::getType();
        if (is_string($typeName) && preg_match('#^[\w\d]+(\\\\[\w\d]+)+$#', $typeName)) {
            // typename is a fully qualified class name, which is good (forward compatible with Symfony 3)
            if (!is_a($typeName, FormTypeInterface::class, true)) {
                throw new \RuntimeException("Not a valid form type " . print_r($typeName, true));
            }
            return $typeName;
        }
        return null;
    }
}
