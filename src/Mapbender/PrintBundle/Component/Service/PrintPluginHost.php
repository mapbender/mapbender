<?php


namespace Mapbender\PrintBundle\Component\Service;


use Mapbender\CoreBundle\Entity\Element;
use Mapbender\PrintBundle\Component\Plugin\PluginBaseInterface;
use Mapbender\PrintBundle\Component\Plugin\PrintClientHttpPluginInterface;
use Mapbender\PrintBundle\Component\Plugin\TextFieldPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PrintPluginHost
{
    /** @var ContainerInterface */
    protected $container;

    /** @var PrintClientHttpPluginInterface[] */
    protected $httpPlugins = array();
    /** @var TextFieldPluginInterface[] */
    protected $textFieldPlugins = array();

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Register a plugin at runtime.
     * This is intended to be called by container compiler passes.
     *
     * @param string|object $plugin container service id or instance
     */
    public function registerPlugin($plugin)
    {
        if (is_string($plugin)) {
            $plugin = $this->container->get($plugin);
        }
        $t = is_object($plugin) ? get_class($plugin) : gettype($plugin);
        if ($plugin instanceof PluginBaseInterface) {
            $key = $plugin->getDomainKey();
            if (!$key || !is_string($key)) {
                throw new \LogicException("Invalid domain key " . print_r($key, true) . " from plugin type {$t}");
            }
            $valid = $this->addPlugin($key, $plugin);
        } else {
            $valid = false;
        }
        if (!$valid) {
            throw new \InvalidArgumentException("Type {$t} does not implement a supported plugin interface, don't know what to do with it");
        }
    }

    /**
     * @param string $key
     * @param object $plugin
     * @return bool
     */
    protected function addPlugin($key, $plugin)
    {
        $valid = false;
        // single plugin may implement multiple plugin interfaces
        // 'iterate' through all known interfaces
        if ($plugin instanceof PrintClientHttpPluginInterface) {
            $this->httpPlugins[$key] = $plugin;
            $valid = true;
        }
        if ($plugin instanceof TextFieldPluginInterface) {
            $this->textFieldPlugins[$key] = $plugin;
            $valid = true;
        }
        return $valid;
    }

    final public function handleHttpRequest(Request $request, Element $elementEntity)
    {
        foreach ($this->httpPlugins as $plugin) {
            $responseOrNull = $plugin->handleHttpRequest($request, $elementEntity);
            if ($responseOrNull) {
                return $responseOrNull;
            }
        }
        // not handled by any plugin
        return null;
    }

    final public function getTextFieldContent($fieldName, $jobData)
    {
        foreach ($this->textFieldPlugins as $plugin) {
            $pluginData = $plugin->getTextFieldContent($fieldName, $jobData);
            if ($pluginData !== null) {
                return $pluginData;
            }
        }
        return null;
    }
}
