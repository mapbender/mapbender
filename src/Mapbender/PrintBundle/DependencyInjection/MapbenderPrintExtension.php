<?php

namespace Mapbender\PrintBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Class MapbenderPrintExtension
 *
 * @package   Mapbender\PrintBundle\DependencyInjection
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class MapbenderPrintExtension extends Extension
{
    const ALIAS              = 'mapbender_print';

    /**
     * Print queues max live age in days key.
     */
    const KEY_MAX_AGE        = 'mapbender.print.max_age';

    /**
     * Prints storage directory relative to web folder key.
     */
    const KEY_STORAGE_DIR    = 'mapbender.print.storage_dir';

    /**
     * Priority voter key.
     */
    const KEY_PRIORITY_VOTER = 'mapbender.print.priority_voter';

    protected $parameters    = array( self::KEY_MAX_AGE,
                                      self::KEY_PRIORITY_VOTER,
                                      self::KEY_STORAGE_DIR);

    /**
     * @inheritdoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // load service and parameters from XML
        (new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config')))->load('services.xml');

        // override parameters
        foreach($this->parameters as $key) {
            if (isset($configs[$key])) {
                $container->setParameter($key,$configs[$key]);
            }
        }

        // override storage_dir
        $container->setParameter(self::KEY_STORAGE_DIR,
            $container->getParameter('kernel.root_dir') . '/../web/' . $container->getParameter(self::KEY_STORAGE_DIR)
        );
    }

    /**
     * @inheritdoc
     * @return string
     */
    public function getAlias()
    {
        return self::ALIAS;
    }
}
