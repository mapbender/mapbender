<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\CoreBundle\Component\ExtendedCollection;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Form\Type\YAMLConfigurationType;
use Symfony\Component\DependencyInjection\ContainerInterface;
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

    /**
     * @param FormFactoryInterface $formFactory
     * @param ContainerInterface $container
     */
    public function __construct(FormFactoryInterface $formFactory, ContainerInterface $container)
    {
        $this->formFactory = $formFactory;
        $this->container = $container;
    }

    /**
     * @param Application $application
     * @param Element $element
     * @return array
     */
    public function getConfigurationForm($application, $element)
    {
        /** @var string $componentClassName */
        $componentClassName = $element->getClass();

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
            $twigTemplate = $componentClassName::getFormTemplate();
        }

        $formType->add('configuration', $configurationType, $options);

        return array(
            'form' => $formType->getForm(),
            'theme' => $twigTemplate,
        );
    }

    /**
     * @param Element $element
     * @return FormTypeInterface|null
     * @throws \RuntimeException
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
     * Look up service-type form type for element. This looks for a service with an id
     * prefixed with 'mapbender.form_type.element.' followed by the all-lowercased class
     * name of the Element component.
     *
     * Currently only HTMLElement has this (service id: mapbender.form_type.element.htmlelement),
     * if only for demonstration purposes.
     *
     * @param Element $element
     * @return object|null
     */
    protected function getServicyConfigurationFormType(Element $element)
    {
        $className = $element->getClass();
        $classNamespaceParts = explode('\\', $className);
        $baseName = strtolower(array_pop($classNamespaceParts));
        $formTypeId = 'mapbender.form_type.element.' . $baseName;
        return $this->container->get($formTypeId, ContainerInterface::NULL_ON_INVALID_REFERENCE);
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
