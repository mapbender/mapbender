<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\Component\BaseElementFactory;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Extension\ElementExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;

/**
 * Service for element configuration and acl forms.
 *
 * Default instance in container as mapbender.manager.element_form_factory.service
 */
class ElementFormFactory extends BaseElementFactory
{
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var bool */
    protected $strict;
    /** @var FormRegistryInterface */
    protected $formRegistry;
    /** @var ElementExtension */
    protected $elementExtension;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ElementInventoryService $inventoryService
     * @param FormRegistryInterface $formRegistry
     * @param ElementExtension $elementExtension
     * @param bool $strict
     */
    public function __construct(FormFactoryInterface $formFactory,
                                ElementInventoryService $inventoryService,
                                FormRegistryInterface $formRegistry,
                                ElementExtension $elementExtension,
                                $strict = false)
    {
        parent::__construct($inventoryService);
        $this->formFactory = $formFactory;
        $this->setStrict($strict);
        $this->formRegistry = $formRegistry;
        $this->elementExtension = $elementExtension;
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
            $options['attr']['data-ft-element-id'] = $element->getId();
        }

        $this->addConfigurationDefaults($element);
        $this->migrateElementConfiguration($element);
        $this->addConfigurationDefaults($element);

        $formType = $this->formFactory->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', $element, $options);
        $formType
            ->add('title', 'Mapbender\ManagerBundle\Form\Type\ElementTitleType', array(
                'element_class' => $element->getClass(),
                'required' => false,
            ))
        ;
        $configurationType = $this->getConfigurationFormType($element);

        $options = array();

        $componentClassName = $this->getComponentClass($element);
        $twigTemplate = $componentClassName::getFormTemplate();
        $options['label'] = false;

        $resolvedType = $this->formRegistry->getType($configurationType);
        if ($resolvedType->getOptionsResolver()->isDefined('application')) {
            // Only pass the "application" option if the form type requires / declares it.
            $options['application'] = $element->getApplication();
        }

        $formType->add('configuration', $configurationType, $options);
        $componentClassName = $this->getComponentClass($element);
        $regionName = $element->getRegion();
        if (\is_a($componentClassName, 'Mapbender\CoreBundle\Component\ElementBase\FloatableElement', true)) {
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
        $componentClassName = $this->getComponentClass($element);
        $typeName = $componentClassName::getType();
        if (is_string($typeName) && preg_match('#^[\w\d]+(\\\\[\w\d]+)+$#', $typeName)) {
            // typename is a fully qualified class name, which is good (forward compatible with Symfony 3)
            if (!is_a($typeName, 'Symfony\Component\Form\FormTypeInterface', true)) {
                throw new \RuntimeException("Not a valid form type " . print_r($typeName, true));
            }
            return $typeName;
        }
        return null;
    }

    public function migrateElementConfiguration(Element $element, $migrateClass = true)
    {
        parent::migrateElementConfiguration($element, $migrateClass);
        $defaultTitle = $this->elementExtension->element_default_title($element);
        if ($element->getTitle() === $defaultTitle) {
            $element->setTitle('');    // @todo: allow null (requires schema update)
        }
    }
}
