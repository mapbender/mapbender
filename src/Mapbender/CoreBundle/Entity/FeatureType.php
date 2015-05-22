<?php
namespace Mapbender\CoreBundle\Entity;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Acl\Exception\Exception;

/**
 * Class FeatureType handles Feature objects.
 *
 * Main goal of the handler is, to get manage GeoJSON Features
 * for communication between OpenLayers and databases
 * with spatial abilities like Oracle or PostgreSQL.
 *
 *
 * @link      https://troubadix.wheregroup.com/wiki/index.php/Mapbender3_Digitalisierung#Schema
 * @package   Mapbender\CoreBundle\Entity
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2015 by WhereGroup GmbH & Co. KG
 */
class FeatureType extends ContainerAware
{
    const ORACLE_PLATFORM     = 'oracle';
    const POSTGRESQL_PLATFORM = 'postgresql';
    const SQLITE_PLATFORM     = 'sqlite';

    /**
     *  Default max results by search
     */
    const MAX_RESULTS = 1000;

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
    protected $sqlFilter;


    /**
     * @param ContainerInterface $container
     * @param null               $args
     */
    public function __construct(ContainerInterface $container, $args = null)
    {
        $this->setContainer($container);
        $hasFields = isset($args["fields"]) && is_array($args["fields"]);

        if (!$hasFields) {
            $args["fields"] = array();
        }

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

        // if no fields defined, but geomField, find it all and remove geo field from the list
        if (!$hasFields && isset($args["geomField"])) {
            $fields = $this->getTableFields();
            unset($fields[array_search($args["geomField"], $fields, false)]);
            $this->setFields($fields);
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
     * Get all table fields
     *
     * @throws \Doctrine\DBAL\DBALException
     * @return array field names
     */
    public function getTableFields()
    {
        $tableName = $this->getTableName();
        $fields    = array();
        $sql       = null;

        switch ($this->getPlatformName()) {
            case self::ORACLE_PLATFORM:
                $sql = "SELECT column_name, data_type, data_length FROM USER_TAB_COLUMNS WHERE table_name = '$tableName'";
                break;

            case self::SQLITE_PLATFORM:
            case self::POSTGRESQL_PLATFORM:
                $sql = "SELECT column_name FROM information_schema.columns WHERE (table_schema || '.' || table_name = '{$tableName}' OR table_name = '{$tableName}')";
                break;
        }

        foreach ($this->fields = $this->getConnection()->executeQuery($sql)->fetchAll() as $fieldInfo) {
            $fields[] = current($fieldInfo);
        }
        return $fields;
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
            $r = $this->getPlatformName() == self::ORACLE_PLATFORM;
        }
        return $r;
    }

    /**
     * Is SQLite platform
     *
     * @return bool
     */
    public function isSqlite()
    {
        static $r;
        if (is_null($r)) {
            $r = $this->getPlatformName() == self::SQLITE_PLATFORM;
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
            $r = $this->getPlatformName() == self::POSTGRESQL_PLATFORM;
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
        if (!is_array($featureData) && !is_object($featureData)) {
            throw new \Exception("Feature data given isn't compatible to save into the table: " . $this->getTableName());
        }

        /** @var Feature $feature */
        $feature    = $this->create($featureData);
        // Insert if no ID given
        if (!$autoUpdate || !$feature->hasId()) {
            $result = $this->insert($feature);
        } // Replace if has ID
        else {
            $result = $this->update($feature);
        }

        return $result;
    }

    /**
     * Insert feature
     *
     * @param array|Feature $featureData
     * @return Feature
     */
    public function insert($featureData)
    {
        /** @var Feature $feature */
        $feature                     = $this->create($featureData);
        $data                        = $this->cleanFeatureData($feature->toArray());
        $connection                  = $this->getConnection();
        $data[$this->getGeomField()] = $this->transformEwkt($data[$this->getGeomField()], $this->getSrid());
        $result                      = $connection->insert($this->tableName, $data);
        $lastId                      = $connection->lastInsertId();

        if($lastId < 1){
            switch ($connection->getDatabasePlatform()->getName()) {
                case self::POSTGRESQL_PLATFORM:
                    $sql    = "SELECT currval(pg_get_serial_sequence('" . $this->tableName . "','" . $this->getUniqueId() . "'))";
                    $lastId =  $connection->executeQuery($sql)->fetchColumn();
                    break;
            }
        }

        $feature->setId($lastId);
        return $feature;
    }

    // TODO: oracle and posgresql switch
    /**
     * @param      $geom
     * @param null $srid
     * @return bool|string
     * @throws \Doctrine\DBAL\DBALException
     */
    public function transformEwkt($geom,$srid=null)
    {
        $srid = $srid ? $srid : $this->getSrid();
        $sql = null;
        switch ($this->getPlatformName()) {
            case self::POSTGRESQL_PLATFORM:
                $sql = "SELECT ST_TRANSFORM(ST_GEOMFROMTEXT('$geom'), $srid)";
                break;
            case self::ORACLE_PLATFORM:
                $sql = "SELECT SDO_CS.TRANSFORM(SDO_UTIL.TO_WKBGEOMETRY('$geom'), $srid)";
                break;
        }

        return $this->connection->executeQuery($sql)->fetchColumn();
    }

    /**
     * Update
     *
     * @param $featureData
     * @return Feature
     * @throws \Exception
     * @internal param array $criteria
     */
    public function update($featureData)
    {
        /** @var Feature $feature */
        $feature                     = $this->create($featureData);
        $data                        = $this->cleanFeatureData($feature->toArray());
        $connection                  = $this->getConnection();
        $data[$this->getGeomField()] = $this->transformEwkt($data[$this->getGeomField()], $this->getSrid());
        unset($data[$this->getUniqueId()]);

        if (empty($data)) {
            throw new \Exception("Feature can't be updated without criteria");
        }

        $connection->update($this->tableName, $data, array($this->uniqueId => $feature->getId()));
        return $feature;
    }

    /**
     * Remove feature
     *
     * @param  Feature|array|int $featureData
     * @return int
     * @throws Exception
     */
    public function remove($featureData)
    {
        $feature = $this->create($featureData);
        $this->getConnection()->delete($this->tableName, array($this->uniqueId => $feature->getId()));
        return $feature;
    }


    /**
     * Search feature by criteria
     *
     * @param array  $criteria
     * @return Feature[]
     */
    public function search(array $criteria = array())
    {

        /** @var Statement $statement */
        /** @var Feature $feature */
        $maxResults   = isset($criteria['maxResults']) ? intval($criteria['maxResults']) : self::MAX_RESULTS;
        $intersect    = isset($criteria['intersectGeometry']) ? $criteria['intersectGeometry'] : null;
        $returnType   = isset($criteria['returnType']) ? $criteria['returnType'] : null;
        $srid         = isset($criteria['srid']) ? $criteria['srid'] : $this->getSrid();
        $queryBuilder = $this->getSelectQueryBuilder($srid);

        // add GEOM where condition
        if ($intersect) {
            $geometry = self::roundGeometry($intersect,2);
            $queryBuilder->andWhere(self::genIntersectCondition($this->getPlatformName(), $geometry, $this->geomField, $srid, $this->getSrid()));
        }

        // add filter (https://trac.wheregroup.com/cp/issues/3733)
        if(!empty($this->sqlFilter)){
            $queryBuilder->andWhere($this->sqlFilter);
        }

        $queryBuilder->setMaxResults($maxResults);
        // $queryBuilder->setParameters($params);
        $statement  = $queryBuilder->execute();
        $rows       = $statement->fetchAll();
        $hasResults = count($rows) > 0;


        // Convert to Feature object
        if ($hasResults) {
            $this->prepareResults($rows,$srid);
        }

        if ($returnType == "FeatureCollection") {
            $rows = $this->toFeatureCollection($rows);
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
     * Generate intersect where condition
     *
     * @param $platformName
     * @param $geometry
     * @param $geometryAttribute
     * @param $srid
     * @return null|string
     */
    public static function genIntersectCondition($platformName, $geometry, $geometryAttribute, $srid, $sridTo)
    {
        $sql = null;
        switch ($platformName) {
            case self::POSTGRESQL_PLATFORM:
                $sql = "ST_INTERSECTS(ST_TRANSFORM(ST_GEOMFROMTEXT('$geometry',$srid),$sridTo),$geometryAttribute)";
                break;
            case self::ORACLE_PLATFORM:
                $sql = "SDO_RELATE($geometryAttribute ,SDO_GEOMETRY(SDO_CS.TRANSFORM('$geometry',$srid),$sridTo), 'mask=ANYINTERACT querytype=WINDOW') = 'TRUE'";
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
     * @param Feature[] $rows
     * @return Feature[]
     */
    public function prepareResults(&$rows,$srid = null)
    {
        // Transform Oracle result column names from upper to lower case
        if ($this->isOracle()) {
            self::transformColumnNames($rows, "strtolower");
        }

        foreach ($rows as $key => &$row) {
            $row = $this->create($row,$srid);
        }

        return $rows;
    }

    /**
     * Get query builder prepared to select from the source table
     *
     * @return QueryBuilder
     */
    public function getSelectQueryBuilder($srid = null)
    {
        $connection         = $this->getConnection();
        $geomFieldCondition = self::getGeomAttribute($this->getPlatformName(), $this->geomField, $srid? $srid: $this->getSrid());
        $spatialFields      = array($this->uniqueId, $geomFieldCondition);
        $attributes         = array_merge($spatialFields, $this->fields);
        $queryBuilder       = $connection->createQueryBuilder()->select($attributes)->from($this->tableName, 't');
        return $queryBuilder;
    }

    /**
     * Cast feature by $args
     *
     * @param $args
     * @return Feature
     */
    public function create($args)
    {
        $feature = null;
        if (is_object($args)) {
            if ($args instanceof Feature) {
                $feature = $args;
            } else {
                $args = get_object_vars($args);
            }
        } elseif (is_numeric($args)) {
            $args = array($this->getUniqueId() => intval($args));
        }
        return $feature ? $feature : new Feature($args, $this->getSrid(), $this->getUniqueId(), $this->getGeomField());
    }

    /**
     * Get SRID
     *
     * @return int
     */
    public function getSrid()
    {
        if(!$this->srid){
            $connection = $this->getConnection();
            switch ($this->getPlatformName()) {
                case self::POSTGRESQL_PLATFORM:
                    $this->srid = $connection->executeQuery("SELECT Find_SRID(concat(current_schema()), '$this->tableName', '$this->geomField')")->fetchColumn();
                    break;
                // TODO: not tested
                case self::ORACLE_PLATFORM:
                    $this->srid = $connection->executeQuery("SELECT {$this->tableName}.{$this->geomField}.SDO_SRID FROM TABLE {$this->tableName}")->fetchColumn();
                    //                $str = "SELECT ST_SRID('$wkt')";
                    //                var_dump($str );
                    //                $srid = $this->getConnection()->executeQuery($str )->fetchColumn();  ;
                    break;
            }
        }
        return $this->srid;
    }

    /**
     * Convert Features[] to FeatureCollection
     *
     * @param Feature[] $rows
     * @return array FeatureCollection
     */
    public function toFeatureCollection($rows)
    {
        /** @var Feature $feature */
        foreach ($rows as $k => $feature) {
            $rows[$k] = $feature->toGeoJson(true);
        }
        return array("type"     => "FeatureCollection",
                     "features" => $rows);
    }

    /**
     * Clean data this can't be saved into db table from data array
     *
     * @param array $data
     * @return array
     */
    private function cleanFeatureData($data)
    {
        $fields = array_merge($this->getFields(), array($this->getUniqueId(), $this->getGeomField()));

        // clean data from feature
        foreach ($data as $fieldName => $value) {
            if (isset($fields[$fieldName])) {
                unset($data[$fieldName]);
            }
        }
        return $data;
    }

    /**
     * Set FeatureType permanent SQL filter used by $this->search()
     * https://trac.wheregroup.com/cp/issues/3733
     *
     * @see $this->search()
     * @param $sqlFilter
     */
    protected function setFilter($sqlFilter){
        $this->sqlFilter = $sqlFilter;
    }

    /**
     * Get sequence name
     *
     * @return string sequence name
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getTableSequenceName(){
        $connection = $this->getConnection();
        $result = $connection->executeQuery("SELECT column_default from information_schema.columns where table_name='" . $this->getTableName() . "' and column_name='" . $this->getUniqueId() . "'")->fetchColumn();
        $result = explode("'",$result);
        return $result[0];
    }

    /**
     * Repair table sequence.
     * Set sequence next ID to (highest ID + 1) in the table
     *
     * @return int last insert ID
     * @throws \Doctrine\DBAL\DBALException
     */
    public function repairTableSequence()
    {
        return $this->getConnection()->executeQuery("SELECT setval('" . $this->getTableSequenceName() . "', (SELECT MAX(" . $this->getUniqueId() . ") FROM " . $this->getTableName() . "))")->fetchColumn();
    }
}