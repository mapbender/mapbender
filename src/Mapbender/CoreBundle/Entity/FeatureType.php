<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Class FeatureType
 * select UpdateGeometrySRID('public', 'bauteil', 'geom', 31467)
 *
 * @package   Mapbender\CoreBundle\Entity
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2015 by WhereGroup GmbH & Co. KG
 */
class FeatureType extends ContainerAware
{
    const ORACLE_PLATFORM     = 'oracle';
    const POSTGRESQL_PLATFORM = 'postgresql';

    /**
     *  Default max results by search
     */
    const MAX_RESULTS = 100;

    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var string Table name
     */
    protected $tableName;

    /**
     * @var mixed Unique id field name
     */
    protected $uniqueId = 'id';

    /**
     * @var string Geometry field name
     */
    protected $geomField = 'geom';

    /**
     * @var int SRID to get geometry converted to
     */
    protected $srid = null;

    /**
     * @var array Dield to select from the table
     */
    protected $fields = array();


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

        if(!$this->srid){
            $this->srid = $this->findSrid();
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
     * @param int $uniqueId
     */
    public function setUniqueId($uniqueId)
    {
        $this->uniqueId = $uniqueId;
    }

    /**
     * @param string $geomField
     */
    public function setGeomField($geomField)
    {
        $this->geomField = $geomField;
    }

    /**
     * @param int $srid
     */
    public function setSrid($srid)
    {
        $this->srid = $srid;
    }

    /**
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->fields = $fields;
    }

    /**
     * Get platform name
     *
     * @return string
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
     * @param array|Feature $featureData
     * @param bool          $autoUpdate update instead of insert if ID given
     * @return Feature
     * @throws \Exception
     */
    public function save($featureData, $autoUpdate = true)
    {
        /** @var Feature $feature */
        $connection = $this->getConnection();
        $data       = array();
        $id         = null;

        // If $featureData is string, posible is an JSON, so try to convert it
        if (is_string($featureData)) {
            $featureData = new Feature($featureData, $this->srid, $this->uniqueId, $this->geomField);
        }

        // If $featureData is object, so collect data as array
        if (is_object($featureData) && $featureData instanceof Feature) {
            $feature     = $featureData;
            $featureData = $feature->getAttributes();

            if ($feature->hasId()) {
                $featureData['id'] = $feature->getId();
            }

            if ($feature->hasGeom()) {
                //$wkb = \geoPHP::load($feature->getGeom(), 'wkt')->out('wkb');
                if ($this->srid) {
                    $featureData['geom'] = "SRID=" . $this->srid . ";" . $feature->getGeom();
                } else {
                    $featureData['geom'] = $this->srid . ";" . $feature->getGeom();
                }
            }
        }

        if (!is_array($featureData)) {
            throw new \Exception("Feature data given isn't compatible to save into the table: " . $this->getTableName());
        }

        // collect input data for the table
        if (isset($featureData[$this->getUniqueId()])) {
            $id = $featureData[$this->getUniqueId()];
        } elseif (isset($featureData['id'])) {
            $id = $featureData['id'];
        }

        if (isset($featureData[$this->getGeomField()])) {
            $data[$this->getGeomField()] = $featureData[$this->getGeomField()];
        } elseif (isset($featureData['geom'])) {
            $data[$this->getGeomField()] = $featureData['geom'];
        }
        foreach ($this->getFields() as $fieldName) {
            if (isset($featureData[$fieldName])) {
                $data[$fieldName] = $featureData[$fieldName];
            }
        }

        if (!count($data)) {
            throw new \Exception("Feature data given is empty");
        }

        // Insert if no ID given
        if (!$autoUpdate || is_null($id)) {

            if (!$autoUpdate) {
                $data[$this->uniqueId] = $id;
            }

            $connection->insert($this->tableName, $data);
            $data['id'] = $connection->lastInsertId();
        } // Replace if has ID
        else {
            $connection->update($this->tableName, $data, array($this->uniqueId => $id));
        }

        $feature = new Feature($data, $this->srid, $this->uniqueId, $this->geomField);
        return $feature;
    }

    /**
     * Remove feature
     *
     * @param Feature|array|int $feature
     * @return int
     * @throws Exception
     */
    public function remove($feature)
    {
        /** @var Feature $feature */
        $id = null;
        if (is_array($feature)) {
            if (isset($feature[$this->uniqueId])) {
                $id = $feature[$this->uniqueId];
            } elseif (isset($feature['id'])) {
                $id = $feature['id'];
            }
        } elseif (is_numeric($feature)) {
            $id = intval($feature);
        } elseif (is_object($feature) && $feature instanceof Feature) {
            $id = $feature->getId();
        }

        if (!is_null($id)) {
            $criteria = array($this->uniqueId => $id);
        } elseif (is_array($feature)) {
            $criteria = $feature;
        } else {
            throw new Exception("Remove of feature with no criteria");
        }

        return $this->getConnection()->delete($this->tableName, $criteria);
    }


    /**
     * Search feature by criteria
     *
     * @param array $criteria
     * @return Feature[]
     */
    public function search(array $criteria = array())
    {
        /** @var Statement $statement */
        $queryBuilder      = $this->getSelectQueryBuilder();
        $maxResults        = isset($criteria['maxResults']) ? intval($criteria['maxResults']) : self::MAX_RESULTS;
        $intersectGeometry = isset($criteria['intersectGeometry']) ? $criteria['intersectGeometry'] : null;

        // add GEOM where condition
        if (!empty($intersectGeometry)) {
            $geometry = self::roundGeometry($intersectGeometry);
            $queryBuilder->andWhere(self::genIntersectCondition($this->getPlatformName(), $intersectGeometry, $this->geomField, $this->srid));
        }

        $queryBuilder->setMaxResults($maxResults);
        // $queryBuilder->setParameters($params);
        $statement  = $queryBuilder->execute();
        $rows       = $statement->fetchAll();
        $hasResults = count($rows) > 1;

        // Convert to Feature object
        if ($hasResults) {
            $this->prepareResults($rows);
        }

        return $rows;
    }

    /**
     * Get feature by ID
     *
     * @param $id
     * @return Feature
     */
    public function getById($id)
    {
        /** @var Statement $statement */
        $queryBuilder = $this->getSelectQueryBuilder();
        $queryBuilder->where($this->getUniqueId() . " = :id");
        $queryBuilder->setParameter('id', $id);
        $statement = $queryBuilder->execute();
        $rows      = $statement->fetchAll();
        $this->prepareResults($rows);
        return reset($rows);
    }

    /**
     * Get unique ID
     *
     * @return mixed unique ID
     */
    public function getUniqueId()
    {
        return $this->uniqueId;
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @return string
     */
    public function getGeomField()
    {
        return $this->geomField;
    }

    /**
     * @return array
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * Transform result column names from lower case to upper
     *
     * @param        $rows         array Two dimensional array link
     * @param string $functionName function name to call for each field name
     */
    public static function transformColumnNames(&$rows, $functionName = "strtolower")
    {
        $columnNames = array_keys(current($rows));
        foreach ($rows as &$row) {
            foreach ($columnNames as $name) {
                $row[$functionName($name)] = &$row[$name];
                unset($row[$name]);
            }
        }
    }

    /**
     * @param $platformName
     * @param $geometry
     * @param $geometryAttribute
     * @param $sridTo
     * @return null|string
     */
    public static function genIntersectCondition($platformName, $geometry, $geometryAttribute, $sridTo)
    {
        $sql = null;
        switch ($platformName) {
            case self::POSTGRESQL_PLATFORM:
                $sql = "ST_INTERSECTS(ST_GEOMFROMTEXT('$geometry',$sridTo),$geometryAttribute)";
                break;
            case self::ORACLE_PLATFORM:
                $sql = "SDO_RELATE( $geometryAttribute ,SDO_GEOMETRY('$geometry',$sridTo), 'mask=ANYINTERACT querytype=WINDOW') = 'TRUE'";
                break;
        }
        return $sql;
    }


    /**
     * Get geometry attribute
     *
     * @param $platformName
     * @param $geometryAttribute
     * @param $sridTo
     * @return null|string
     */
    public static function getGeomAttribute($platformName, $geometryAttribute, $sridTo)
    {
        $sql = null;
        switch ($platformName) {
            case self::POSTGRESQL_PLATFORM:
                $sql = "ST_ASTEXT(ST_TRANSFORM($geometryAttribute, $sridTo)) AS $geometryAttribute";
                break;
            case self::ORACLE_PLATFORM:
                $sql = "SDO_UTIL.TO_WKTGEOMETRY(SDO_CS.TRANSFORM($geometryAttribute, $sridTo)) AS $geometryAttribute";
                break;
        }
        return $sql ? $sql : $geometryAttribute;
    }


    /**
     *
     * Round geometry up to $round parameter.
     *
     * Default: geometry round = 0.2
     *
     * @param string $geometry WKT
     * @return string WKT
     */
    public static function roundGeometry($geometry, $round = 2)
    {
        return preg_replace("/(\\d+)\\.(\\d{$round})\\d+/", '$1.$2', $geometry);
    }

    /**
     * Convert results to Feature objects
     *
     * @param $rows
     * @return array
     */
    public function prepareResults(&$rows)
    {
        // Transform Oracle result column names from upper to lower case
        if ($this->isOracle()) {
            self::transformColumnNames($rows, "strtolower");
        }

        foreach ($rows as $key => &$row) {
            $row = new Feature($row, $this->srid, $this->getUniqueId(), $this->getGeomField());
        }

        return $rows;
    }

    /**
     * Get query builder prepared to select from the source table
     *
     * @return QueryBuilder
     */
    public function getSelectQueryBuilder()
    {
        $connection         = $this->getConnection();
        $geomFieldCondition = self::getGeomAttribute($this->getPlatformName(), $this->geomField, $this->srid);
        $spatialFields      = array($this->uniqueId, $geomFieldCondition);
        $attributes         = array_merge($spatialFields, $this->fields);
        $queryBuilder       = $connection->createQueryBuilder()->select($attributes)->from($this->tableName, 't');
        return $queryBuilder;
    }

    /**
     * Create feature
     *
     * @param $args
     * @return Feature
     */
    public function create($args) {
        return new Feature($args, $this->srid, $this->uniqueId, $this->geomField);
    }

    /**
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function findSrid()
    {
        $srid       = null;
        $connection = $this->getConnection();
        switch ($this->getPlatformName()) {
            case self::POSTGRESQL_PLATFORM:
                $srid = $connection->executeQuery("SELECT Find_SRID(concat(current_schema()), '$this->tableName', '$this->geomField')")->fetchColumn();
            // TODO: not tested
            case self::ORACLE_PLATFORM:
                $srid = $connection->executeQuery("SELECT {$this->tableName}.{$this->geomField}.SDO_SRID FROM TABLE {$this->tableName}")->fetchColumn();
        }
        return $srid;
    }

//    public function findSridByWkt($wkt){
//        switch($this->getPlatformName()){
//            case self::POSTGRESQL_PLATFORM:
//                $str = "SELECT ST_SRID('$wkt')";
//                var_dump($str );
//                $srid = $this->getConnection()->executeQuery($str )->fetchColumn();  ;
//        }
//        return $srid;
//    }
}