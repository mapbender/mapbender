<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\FeatureType;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Features service handles feature types
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 18.03.2015 by WhereGroup GmbH & Co. KG
 * @package   Mapbender\CoreBundle\Component
 */
class Features extends ContainerAware
{
    /**
     * Feature type s defined in mapbebder.yml > parameters.featureTypes
     *
     * @var FeatureType[] feature types
     */
    private $featureTypes = array();

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    /**
     * Get feature type by
     *
     * @param $featureTypeName
     * @return FeatureType
     */
    public function get($featureTypeName)
    {
        static $parameters = null;
        if (!isset($this->featureTypes[$featureTypeName])) {
            if (!$parameters) {
                $parameters = $this->container->getParameter('featureTypes');
            }
            $this->featureTypes[$featureTypeName] = new FeatureType($this->container, $parameters[$featureTypeName]);
        }
        return $this->featureTypes[$featureTypeName];
    }
}