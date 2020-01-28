<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\Component\BaseElementFactory;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Component\ExtendedCollection;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Service for element configuration and acl forms.
 *
 * Default instance in container as mapbender.manager.element_form_factory.service
 */
class ElementFormFactory extends BaseElementFactory
{
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var ContainerInterface */
    protected $container;
    /** @var bool */
    protected $strict;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ElementInventoryService $inventoryService
     * @param ContainerInterface $container
     * @param bool $strict
     */
    public function __construct(FormFactoryInterface $formFactory,
                                ElementInventoryService $inventoryService,
                                ContainerInterface $container, $strict = false)
    {
        parent::__construct($inventoryService);
        $this->formFactory = $formFactory;
        $this->container = $container;
        $this->setStrict($strict);
    }

    public function setStrict($enable)
    {
        $this->strict = !!$enable;
    }

    /**
     * @param Element $element
     * @param mixed[] $options forwarded to form builder
     * @return array
     */
    public function getConfigurationForm($element, $options = array())
    {
        // Create base form shared by all elements
        $formType = $this->formFactory->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', $element, $options);
        $formType
            ->add('title', 'Symfony\Component\Form\Extension\Core\Type\TextType')
        ;
        $this->migrateElementConfiguration($element);
        $configurationType = $this->getConfigurationFormType($element);

        $options = array('application' => $element->getApplication());
        if ($configurationType instanceof ExtendedCollection && $element !== null && $element->getId() !== null) {
            $options['element'] = $element;
        }

        if ($configurationType === null) {
            $configurationType = $this->getFallbackConfigurationFormType($element);
            unset($options['application']);
            $twigTemplate = 'MapbenderManagerBundle:Element:yaml-form.html.twig';
        } else {
            $componentClassName = $this->getComponentClass($element);
            $twigTemplate = $componentClassName::getFormTemplate();
        }

        $formType->add('configuration', $configurationType, $options);

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
     * @throws ServiceNotFoundException
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

        $type = $this->getServicyConfigurationFormType($element);
        if ($type) {
            $this->deprecated(
                "Detected element form type service for {$componentClassName}. Please stop relying on "
              . "re-plugging form type services, and instead use form type extensions to transparently alter existing types. "
              . "See https://symfony.com/doc/2.8/form/create_form_type_extension.html");

            if (!($type instanceof FormTypeInterface)) {
                throw new \RuntimeException("Invalid form type " . get_class($type) . " from " . print_r($componentClassName, true));
            }
            return \get_class($type);
        }
        return null;
    }

    public function getFallbackConfigurationFormType(Element $element)
    {
        return 'Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType';
    }

    /**
     * Look up service-type form type for element. If the Element Component's getType return value looks like
     * a service id (=string containing at least one dot), that service will be returned.
     *
     * Otherwise, looks for a service with an automatically inferred id composed of a 'mapbender.form_type.element.'
     * prefix followed by the all-lowercased class name of the Element Component.
     *
     * Automatic service id inference is deprecated and will throw in strict mode.
     *
     * @param Element $element
     * @return object|null
     * @throws ServiceNotFoundException
     */
    protected function getServicyConfigurationFormType(Element $element)
    {
        $componentClassName = $this->getComponentClass($element);
        $typeDeclaration = $componentClassName::getType();
        if (is_string($typeDeclaration) && false !== strpos($typeDeclaration, '.')) {
            return $this->container->get($typeDeclaration);
        }
        /** @todo: remove automatic service name inflection magic */
        $classNamespaceParts = explode('\\', $componentClassName);
        $baseName = strtolower(array_pop($classNamespaceParts));
        $automaticServiceId = 'mapbender.form_type.element.' . $baseName;
        $automaticService = $this->container->get($automaticServiceId, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if ($automaticService && $typeDeclaration !== $automaticServiceId) {
            $this->deprecated("Located undeclared servicy form type {$automaticServiceId} for {$componentClassName}; return the fully qualified class name of the form type from your element's getType instead");
        }
        return $automaticService; // may also be null
    }
}
