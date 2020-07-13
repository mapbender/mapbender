<?php


namespace Mapbender\ManagerBundle\Component;


use Mapbender\Component\BaseElementFactory;
use Mapbender\CoreBundle\Component\ElementInventoryService;
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

        $componentClassName = $this->getComponentClass($element);
        $twigTemplate = $componentClassName::getFormTemplate();

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
