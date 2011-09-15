<?php

namespace Mapbender\WmsBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Mapbender WMS extension loader
 *
 * @author Christian Wygoda <christian.wygoda@wheregroup.com>
 */
class MapbenderWmsExtension extends Extension {
	public function load(array $configs, ContainerBuilder $container) {
		$loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
		$loader->load('services.xml');
	}

	public function getAlias() {
		return 'mapbender_wms';
	}
}

