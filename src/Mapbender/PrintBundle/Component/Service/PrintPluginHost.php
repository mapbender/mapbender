<?php


namespace Mapbender\PrintBundle\Component\Service;


use Mapbender\CoreBundle\Entity\Element;
use Mapbender\PrintBundle\Component\Plugin\PrintClientHttpPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PrintPluginHost
{
    /** @var ContainerInterface */
    protected $container;

    /** @var PrintClientHttpPluginInterface[] */
    protected $httpPlugins = array();

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
        $valid = false;
        $t = is_object($plugin) ? get_class($plugin) : gettype($plugin);
        if ($plugin instanceof PrintClientHttpPluginInterface) {
            $key = $plugin->getDomainKey();
            if (!$key || !is_string($key)) {
                throw new \LogicException("Invalid domain key " . print_r($key, true) . " from plugin type {$t}");
            }
            $this->httpPlugins[$key] = $plugin;
            $valid = true;
        }
        // @todo: support plugins for other stuff (modifying pdf, modifying map image) here?
        if (!$valid) {
            throw new \InvalidArgumentException("Type {$t} does not implement a supported plugin interface, don't know what to do with it");
        }
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
}
