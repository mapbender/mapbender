<?php


namespace Mapbender\Component\Element;

use Mapbender\CoreBundle\Component\ElementBase\BoundSelfRenderingInterface;
use Mapbender\CoreBundle\Component\ElementInterface;
use Mapbender\CoreBundle\Component\Exception\InvalidElementClassException;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\Mapper;
use Mapbender\Utils\AssetReferenceUtil;
use Psr\Container\ContainerInterface;

/**
 * Bridges non-static AbstractElementService API to Component\Element.
 * Multiplexing facade handling many / all Component\Element instances of the
 * same class.
 */
class ElementShim extends AbstractElementService
    implements ImportAwareInterface
{
    /** @var string */
    protected $handlingClassName;
    /** @var ContainerInterface */
    protected $componentContainer;
    /** @var ElementInterface[] */
    protected $components = array();
    /** @var ElementHttpShim[] */
    protected $httpHandlers = array();
    /** @var boolean|null (lazy initialized to boolean) */
    protected $httpSupport = null;
    /** @var string[][] */
    protected $assets = array();

    public function __construct(ContainerInterface $componentContainer, $handlingClassName)
    {
        $this->handlingClassName = $handlingClassName;
        $this->componentContainer = $componentContainer;
    }

    public function getWidgetName(Element $element)
    {
        return $this->getComponent($element)->getWidgetName();
    }

    public function getClientConfiguration(Element $element)
    {
        return $this->getComponent($element)->getPublicConfiguration();
    }

    public function getRequiredAssets(Element $element)
    {
        $key = \spl_object_id($element);
        if (!\array_key_exists($key, $this->assets)) {
            $component = $this->getComponent($element);
            $references = $component->getAssets() + array(
                'js' => array(),
                'css' => array(),
                'trans' => array(),
            );
            foreach (array('js', 'css') as $type) {
                $references[$type] = AssetReferenceUtil::qualifyBulk($component, $references[$type], false);
            }
            $this->assets[$key] = $references;
        }
        return $this->assets[$key];
    }

    public function getView(Element $element)
    {
        return new LegacyView($this->getComponent($element)->render());
    }

    public function getHttpHandler(Element $element)
    {
        if ($this->httpSupport === null) {
            $this->httpSupport = $this->detectHttpSupport();
        }
        if (!$this->httpSupport) {
            return null;
        }
        $key = \spl_object_id($element);
        if (!array_key_exists($key, $this->httpHandlers)) {
            /** @var \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface $component */
            $component = $this->getComponent($element);
            assert($component instanceof \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface);
            $this->httpHandlers[$key] = new ElementHttpShim($component);
        }
        return $this->httpHandlers[$key] ?: null;
    }

    /**
     * @param Element $element
     * @return ElementInterface|BoundSelfRenderingInterface
     */
    protected function getComponent(Element $element)
    {
        $key = \spl_object_id($element);
        if (empty($this->components[$key])) {
            $hc = $this->handlingClassName;
            if (!$hc) {
                throw new InvalidElementClassException($element->getClass(), "No class implementation for {$element->getClass()}");
            }
            /** @see \Mapbender\CoreBundle\Component\Element::__construct */
            $instance = new $hc($this->componentContainer, $element);
            if (!$instance instanceof ElementInterface) {
                throw new InvalidElementClassException($hc, "Incompatible class signature on {$hc} handling {$element->getClass()}");
            }

            $this->components[$key] = $instance;
        }
        return $this->components[$key];
    }

    /**
     * @return boolean
     */
    protected function detectHttpSupport()
    {
        if (!\is_a($this->handlingClassName, 'Mapbender\CoreBundle\Component\ElementHttpHandlerInterface', true)) {
            return false;
        }
        try {
            $refl = new \ReflectionClass($this->handlingClassName);
        } catch (\ReflectionException $e) {
            return false;
        }
        $baseClassName = 'Mapbender\CoreBundle\Component\Element';
        /** @see \Mapbender\CoreBundle\Component\ElementHttpHandlerInterface::handleHttpRequest $hasHttp */
        $handlerMethod = $refl->getMethod('handleHttpRequest');
        $declaring = $handlerMethod->getDeclaringClass()->getName();
        $hasHttp = $declaring !== $baseClassName;
        if (!$hasHttp && $refl->hasMethod('httpAction')) {
            $declaring = $refl->getMethod('httpAction')->getDeclaringClass()->getName();
            $hasHttp = $declaring !== $baseClassName;
        }
        return $hasHttp;
    }

    public function onImport(Element $element, Mapper $mapper)
    {
        $component = $this->getComponent($element);
        if ($component instanceof \Mapbender\CoreBundle\Component\Element) {
            $configOut = $component->denormalizeConfiguration($element->getConfiguration(), $mapper);
            $element->setConfiguration($configOut);
        }
    }

    /** @todo: split service element interface to not contain any static methods */
    public static function getType()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getFormTemplate()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getDefaultConfiguration()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getClassTitle()
    {
        throw new \LogicException("Cannot access shim method statically");
    }

    public static function getClassDescription()
    {
        throw new \LogicException("Cannot access shim method statically");
    }
}
