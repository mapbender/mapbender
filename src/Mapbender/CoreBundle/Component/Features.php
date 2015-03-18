<?php
namespace Mapbender\CoreBundle\Component;

use Mapbender\CoreBundle\Entity\Feature;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class Features
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 18.03.2015 by WhereGroup GmbH & Co. KG
 * @package   Mapbender\CoreBundle\Component
 */
class Features extends ContainerAware
{
    const ORACLE_PLATFORM     = 'oracle';
    const POSTGRESQL_PLATFORM = 'postgresql';

    public function __construct(ContainerInterface $container = null)
    {
        $this->setContainer($container);
    }

    /**
     * Save feature
     *
     * @param $featureData
     * @return Feature
     */
    public function save($featureData)
    {
        $feature = is_array($featureData) ? new Feature($featureData) : $featureData;
        return $feature;
    }

    /**
     * Get DBAL connection service, either given one or default one
     *
     * @param $name
     * @internal param $config
     * @return Connection
     */
    protected function db($name = 'default')
    {
        return $this->container->get("doctrine.dbal.{$name}_connection");
    }

    /**
     * @return mixed
     */
    protected function logger()
    {
        return $this->container->get('logger');
    }

    /**
     * @param $name
     *
     * @internal param $connection
     * @return mixed
     */
    private function getPlatformName($name)
    {
        return $this->db($name)->getDatabasePlatform()->getName();
    }

    /**
     * Get GEOM attribute
     *
     * @param $connectionName
     * @param $geometryAttribute
     * @param $sridTo
     *
     * @internal param $platformName
     * @return string
     */
    private function getGeomAttribute($connectionName, $geometryAttribute, $sridTo)
    {
        $platformName = $this->getPlatformName($connectionName);
        switch ($platformName) {
            case self::ORACLE_PLATFORM:
                $geomKey = "SDO_UTIL.TO_WKTGEOMETRY(SDO_CS.TRANSFORM($geometryAttribute, $sridTo)) AS $geometryAttribute";
                break;
            case self::POSTGRESQL_PLATFORM:
                $geomKey = "ST_ASTEXT(ST_TRANSFORM($geometryAttribute, $sridTo)) AS $geometryAttribute";
                break;
            default:
                $geomKey = $geometryAttribute;
                return $geomKey;
        }
        return $geomKey;
    }

    /**
     * Transform result column names from lower case to upper
     *
     * @param $rows array Two dimensional array link
     */
    private function transformResultColumnNamesToUpperCase(&$rows)
    {
        $columnNames = array_keys(current($rows));
        foreach ($rows as &$row) {
            foreach ($columnNames as $name) {
                $row[strtoupper($name)] = &$row[$name];
                unset($row[$name]);
            }
        }
    }
}