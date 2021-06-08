<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\MainMapElementInterface;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity;
use Mapbender\CoreBundle\Entity\SRS;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * Map element.
 *
 * @author Christian Wygoda
 */
class Map extends Element implements MainMapElementInterface, ConfigMigrationInterface
{

    const MINIMUM_TILE_SIZE = 128;

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.map.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.map.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        /* "standardized rendering pixel size" for WMTS 0.28 mm × 0.28 mm -> DPI for WMTS: 90.714285714 */
        return array(
            'layersets' => array(),
            'srs' => 'EPSG:4326',
            'otherSrs' => array("EPSG:31466", "EPSG:31467"),
            'tileSize' => 512,
            'extents' => array(
                'max' => array(0, 40, 20, 60),
                'start' => array(5, 45, 15, 55)),
            "scales" => array(25000000, 10000000, 5000000, 1000000, 500000),
            'fixedZoomSteps' => false,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbMap';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.map.js',
            ),
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/map.scss'));
    }

    /**
     * Returns a list of all configured SRSes, producing an array with 'name' and 'title' for each
     * @return string[][]
     */
    protected function buildSrsConfigs()
    {
        $customTitles = array();
        $configuration = $this->entity->getConfiguration();
        $mainSrsParts  = preg_split("/\s*\|\s*/", trim($configuration["srs"]));
        $defaultSrsName = $mainSrsParts[0];
        $configuration['srs'] = $defaultSrsName;
        if (!empty($mainSrsParts[1])) {
            $customTitles[$mainSrsParts[0]] = $mainSrsParts[1];
        }
        $srsNames = array($defaultSrsName);
        if (!empty($configuration['otherSrs'])) {
            $otherSrsConfigs = $configuration['otherSrs'];
            if (\is_string($otherSrsConfigs)) {
                $otherSrsConfigs = preg_split('/\s*,\s*/', trim($otherSrsConfigs));
            }

            foreach ($otherSrsConfigs as $srs) {
                $otherSrsParts = preg_split("/\s*\|\s*/", trim($srs));
                if ($otherSrsParts[0] !== $defaultSrsName) {
                    $srsNames[] = $otherSrsParts[0];
                    if (!empty($otherSrsParts[1])) {
                        $customTitles[$otherSrsParts[0]] = $otherSrsParts[1];
                    }
                }
            }
        }
        $defs = $this->getSrsDefinitions($srsNames);
        foreach ($defs as $i => $def) {
            if (!empty($customTitles[$def['name']])) {
                $defs[$i]['title'] = $customTitles[$def['name']];
            }
        }
        return array(
            'srs' => $defaultSrsName,
            'srsDefs' => $defs,
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $defaultConfiguration = $this->getDefaultConfiguration();
        $configuration        = parent::getConfiguration();
        $configuration += $this->buildSrsConfigs();

        if (!isset($configuration["tileSize"])) {
            $configuration["tileSize"] = $defaultConfiguration["tileSize"];
        } else {
            $configuration["tileSize"] = max(self::MINIMUM_TILE_SIZE, $configuration["tileSize"]);
        }

        return $configuration;
    }

    public function getPublicConfiguration()
    {
        $conf = $this->getConfiguration();
        if ($conf['scales']) {
            $conf['scales'] = array_values(array_map('intval', $conf['scales']));
        }
        return $conf;
    }

    /**
     * @inheritdoc
     */
    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderCoreBundle:Element:map{$suffix}";
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\MapAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:map.html.twig';
    }

    /**
     * Returns proj4js srs definitions from srs names
     * @param string[] $names
     * @return string[][]
     */
    protected function getSrsDefinitions(array $names)
    {
        /** @var RegistryInterface $managerRegistry */
        $managerRegistry = $this->container->get('doctrine');
        $srsRepository = $managerRegistry->getRepository('MapbenderCoreBundle:SRS');
        /** @var SRS[] $srses */
        $srses = $srsRepository->findBy(array(
            'name' => $names,
        ));
        $defs = array();
        foreach ($srses as $srs) {
            $defs[] = array(
                'name' => $srs->getName(),
                'title' => $srs->getTitle(),
                'definition' => $srs->getDefinition(),
            );
        }
        return $defs;
    }

    /**
     * @inheritdoc
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        if (key_exists('extent_start', $configuration) && key_exists('extent_start', $configuration)) {
            $configuration['extents'] = array(
                'start' => $configuration['extent_start'],
                'max' => $configuration['extent_max']
            );
            unset($configuration['extent_start']);
            unset($configuration['extent_max']);
        }
        if (is_string($configuration['otherSrs'])) {
            $configuration['otherSrs'] = explode(',', $configuration['otherSrs']);
        }
        if (is_string($configuration['scales'])) {
            $configuration['scales'] = explode(',', $configuration['scales']);
        }

        if (key_exists('layersets', $configuration)) {
            foreach ($configuration['layersets'] as &$layerset) {
                $layerset = $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\Layerset', $layerset);
            }
        }
        return $configuration;
    }

    public static function updateEntityConfig(Entity\Element $entity)
    {
        $config = $entity->getConfiguration();
        if (isset($config['layerset']) && !isset($config['layersets'])) {
            // legacy db config, promote to array-form 'layersets'
            $config['layersets'] = (array)$config['layerset'];
        }
        unset($config['layerset']);
        $entity->setConfiguration($config);
    }
}
