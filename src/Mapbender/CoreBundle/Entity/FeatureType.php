<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FeatureType
 *
 * @package   Mapbender\CoreBundle\Entity
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2015 by WhereGroup GmbH & Co. KG
 */
class FeatureType extends ContainerAware
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string Table name
     */
    protected $tableName;

    /**
     * @param ContainerInterface $container
     * @param null               $args
     */
    public function __construct(ContainerInterface $container, $args = null)
    {
        $this->setContainer($container);

        // init $methods by $args
        if (is_array($args)) {
            $methods = get_class_methods(get_class($this));
            foreach ($args as $key => $value) {
                $keyMethod = "set" . ucwords($key);
                if (in_array($keyMethod, $methods)) {
                    $this->$keyMethod($value);
                }
            }
        }
    }

    /**
     * Set connection
     *
     * @param $name
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $this->container->get("doctrine.dbal.{$name}_connection");
        return $this;
    }

    /**
     * Set table name
     *
     * @param $name
     * @return $this
     */
    public function setTable($name)
    {
        $this->tableName = $name;
        return $this;
    }

    /**
     * Get DBAL Connection
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Get platform name
     *
     * @return mixed
     */
    public function getPlatformName()
    {
        static $name = null;
        if (!$name) {
            $name = $this->getConnection()->getDatabasePlatform()->getName();
        }
        return $name;
    }

    /**
     * Is oralce platform
     *
     * @return bool
     */
    public function isOracle()
    {
        static $r;
        if (is_null($r)) {
            $r = $this->getPlatformName() == 'oracle';
        }
        return $r;
    }

    /**
     * Is postgres platform
     *
     * @return bool
     */
    public function isPostgres()
    {
        static $r;
        if (is_null($r)) {
            $r = $this->getPlatformName() == 'postgresql';
        }
        return $r;
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
        if ($this->isOracle()) {
            $geomKey = "SDO_UTIL.TO_WKTGEOMETRY(SDO_CS.TRANSFORM($geometryAttribute, $sridTo)) AS $geometryAttribute";
        } elseif ($this->isPostgres()) {
            $geomKey = "ST_ASTEXT(ST_TRANSFORM($geometryAttribute, $sridTo)) AS $geometryAttribute";
        } else {
            $geomKey = $geometryAttribute;
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