<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\CoreBundle\Component\ExtendedCollection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Service for element configuration and acl forms.
 *
 * Default instance in container as mapbender.manager.element_form_factory.service
 */
class ElementFormFactory
{
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var ContainerInterface */
    protected $container;
    /** @var bool */
    protected $strict;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ContainerInterface $container
     * @param bool $strict
     */
    public function __construct(FormFactoryInterface $formFactory, ContainerInterface $container, $strict = false)
    {
        $this->formFactory = $formFactory;
        $this->container = $container;
        $this->setStrict($strict);
    }

    public function setStrict($enable)
    {
        $this->strict = !!$enable;
    }

    /**
     * @param Application $application
     * @param Element $element
     * @return array
     */
    public function getConfigurationForm($application, $element)
    {

        // Create base form shared by all elements
        $formType = $this->formFactory->createBuilder('form', $element, array());
        $formType
            ->add('title', 'text')
            ->add('class', 'hidden')
            ->add('region', 'hidden')
        ;
        $configurationType = $this->getConfigurationFormType($element);

        $options = array('application' => $application);
        if ($configurationType instanceof ExtendedCollection && $element !== null && $element->getId() !== null) {
            $options['element'] = $element;
        }
        if ($configurationType === null) {
            $configurationType = $this->getFallbackConfigurationFormType($element);
            $twigTemplate = 'MapbenderManagerBundle:Element:yaml-form.html.twig';
        } else {
            $componentClassName = $element->getClass();
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
     * @return FormTypeInterface|null
     * @throws \RuntimeException
     * @throws ServiceNotFoundException
     */
    public function getConfigurationFormType(Element $element)
    {
        $componentClassName = $element->getClass();
        $type = $this->getServicyConfigurationFormType($element) ?: ($componentClassName::getType());
        if ($type === null) {
            return null;
        }

        if (is_string($type)) {
            if (is_a($type, 'Symfony\Component\Form\FormTypeInterface', true)) {
                return new $type();
            } else {
                throw new \RuntimeException("No such form type " . print_r($type, true));
            }
        }

        if ($type instanceof FormTypeInterface) {
            return $type;
        } else {
            throw new \RuntimeException("Invalid form type from " . print_r($componentClassName, true));
        }
    }

    public function getFallbackConfigurationFormType(Element $element)
    {
        return new YAMLConfigurationType();
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
        $componentClassName = $element->getClass();
        $typeDeclaration = $componentClassName::getType();
        if (is_string($typeDeclaration) && false !== strpos($typeDeclaration, '.')) {
            return $this->container->get($typeDeclaration);
        }
        $classNamespaceParts = explode('\\', $componentClassName);
        $baseName = strtolower(array_pop($classNamespaceParts));
        $automaticServiceId = 'mapbender.form_type.element.' . $baseName;
        $automaticService = $this->container->get($automaticServiceId, ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if ($automaticService && $typeDeclaration !== $automaticServiceId) {
            $this->deprecated("Located undeclared servicy form type {$automaticServiceId} for {$componentClassName}, please return the full service name explicitly from your getType implementation");
        }
        return $automaticService; // may also be null
    }

    /**
     * @param Element $element
     * @return array
     */
    public function getSecurityForm(Element $element)
    {
        $formType = $this->formFactory->createBuilder('form', $element, array());
        $formType->add('acl', 'acl', array(
            'mapped' => false,
            'data' => $element,
            'create_standard_permissions' => false,
            'permissions' => array(
                1 => 'View',
            ),
        ));
        return array(
            'form' => $formType->getForm(),
        );
    }
}
