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
        /** @var string $class */
        $class = $element->getClass();

        // Create base form shared by all elements
        $formType = $this->formFactory->createBuilder('form', $element, array());
        $formType
            ->add('title', 'text')
            ->add('class', 'hidden')
            ->add('region', 'hidden')
        ;
        // Get configuration form, either basic YAML one or special form
        $configurationFormType = $class::getType();
        if ($configurationFormType === null) {
            $formType->add(
                'configuration',
                new YAMLConfigurationType(),
                array(
                    'required' => false,
                    'attr' => array(
                        'class' => 'code-yaml'
                    )
                )
            );
            $formTheme = 'MapbenderManagerBundle:Element:yaml-form.html.twig';
        } else {
            $type = $this->getAdminFormType($configurationFormType, $class);

            $options = array('application' => $application);
            if ($type instanceof ExtendedCollection && $element !== null && $element->getId() !== null) {
                $options['element'] = $element;
            }

            $formType->add('configuration', $type, $options);
            $formTheme = $class::getFormTemplate();
        }

        return array(
            'form' => $formType->getForm(),
            'theme' => $formTheme,
        );
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

    protected function getAdminFormType($configurationFormType, $class)
    {
        $classNamespaceParts = explode('\\', $class);
        $baseName = strtolower(array_pop($classNamespaceParts));
        $formTypeId = 'mapbender.form_type.element.' . $baseName;
        $serviceExists = $this->container->has($formTypeId);

        if ($serviceExists) {
            $adminFormType = $this->container->get($formTypeId);
        } else {
            $adminFormType = new $configurationFormType();
        }
        /** @var FormTypeInterface $adminFormType */
        return $adminFormType;
    }
}
