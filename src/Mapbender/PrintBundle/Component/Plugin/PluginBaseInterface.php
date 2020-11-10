<?php


namespace Mapbender\PrintBundle\Component\Plugin;


interface PluginBaseInterface
{
    /**
     * Should return a string identifying the problem domain of the plugin.
     * This is used so shipping core plugins can be replaced. If multiple plugins register with the same domain key,
     * the last one will win.
     * @return string
     */
    public function getDomainKey();
}
