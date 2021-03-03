<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\Component\BaseElementFactory;
use Mapbender\CoreBundle\Component\ElementInventoryService;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormRegistryInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

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
    /** @var FormRegistryInterface */
    protected $formRegistry;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ElementInventoryService $inventoryService
     * @param ContainerInterface $container
     * @param FormRegistryInterface $formRegistry
     * @param bool $strict
     */
    public function __construct(FormFactoryInterface $formFactory,
                                ElementInventoryService $inventoryService,
                                ContainerInterface $container,
                                FormRegistryInterface $formRegistry,
                                $strict = false)
    {
        parent::__construct($inventoryService);
        $this->formFactory = $formFactory;
        $this->container = $container;
        $this->setStrict($strict);
        $this->formRegistry = $formRegistry;
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
        // Add class and element id data attributes for functional test support
        $options += array('attr' => array());
        $options['attr']['class'] = trim(ArrayUtil::getDefault($options['attr'], 'class', '') . ' -ft-element-form');
        if ($element->getId()) {
            $options['attr']['data-ft-element-id'] = $element->getId();
        }

        $formType = $this->formFactory->createBuilder('Symfony\Component\Form\Extension\Core\Type\FormType', $element, $options);
        $this->migrateElementConfiguration($element);
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

}
