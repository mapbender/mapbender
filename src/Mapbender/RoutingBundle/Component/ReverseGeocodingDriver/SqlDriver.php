<?php

namespace Mapbender\RoutingBundle\Component\ReverseGeocodingDriver;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Registry as DoctrineRegistry;
use Mapbender\DataSourceBundle\Component\Drivers\Interfaces\Geographic;
use Mapbender\DataSourceBundle\Component\Drivers\Interfaces\Manageble;
use Mapbender\DataSourceBundle\Component\Drivers\Interfaces\Routable;
use Mapbender\DataSourceBundle\Component\Drivers\PostgreSQL;

class SqlDriver extends PostgreSQL implements Geographic
{
    /**
     * @var string
     */
    protected $name='reverseGeocoding';

    /**
     * @var string
     */
    protected $geometryColumn = '';

    /**
     * @var string
     */
    protected $tableName = '';

    /**
     * @var string
     */
    protected $searchColumn = '';

    /**
     * @var string
     */
    protected $responseType = 'json';

    /**
     * @var array
     */
    protected $coordinates = array();

    /**
     * @var string
     */
    private $sridGeom = '';

    /**
     * @var string
     */
    protected $viewSrid = '';

    /**
     * @var Connection|mixed
     */
    public $connection;

    /**
     * @var array
     */
    protected $responseData;

    /**
     * @var int
     */
    protected $searchBuffer = 50;

    protected DoctrineRegistry $doctrine;

    public function __construct(DoctrineRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
        /*
        $this->geometryColumn = $config['geometryColumn'];
        $this->tableName = $config['tableName'];
        $this->searchColumn = $config['searchColumn'];
        $this->searchBuffer = $config['searchBuffer'];
        $this->viewSrid = $requestParams['srsId'];
        $this->coordinates = $requestParams['coordinate'];
        $this->connection = $connection;
        */
    }


    /**
     * get RouteResult and transfrom to ViewSRID
     * @throws DBALException
     */
    public function getRevGeoCodeResult(): ?array
    {
        $result= null;
        $tableName = null;
        $listEwkt= null;

        /* get point-Geom-Grid from default tabelName*/
        $this->sridGeom = $this->getPointGeomGrid();

        /* create List of Coords in the Wkt-Format*/
        $wkt = $this->convertCoordToWkt();

        /* get nearObject as array */
        $result = $this->getFindNearObject($wkt[0]);

        return $result;

    }

    private function getSchemaName() {
        if (strpos($this->tableName, '.')) {
            return explode('.', $this->tableName[0]);
        }
        return "public";
    }

    /**
     * Find Near Object by Wkt
     * @param $dbName
     * @param $wkt
     * @return array
     * @throws DBALException
     */
    private function getFindNearObject($wkt): ?array
    {
        $connection = $this->getConnection();
        $geom = $this->getPointGeomFieldName();
        $viewSridInput = str_replace('EPSG:','', $this->viewSrid);
        $pointSridInput = str_replace('EPSG:','', $this->getPointGeomGrid());
        $queryString = null;
        $result = null;

        if($this->doesTableExist()){
            $queryString = "
            WITH pt AS (
                SELECT ST_Transform(ST_GeomFromText('".$wkt."',$viewSridInput), $pointSridInput) AS inputgeom
            )
            SELECT
              ".$this->searchColumn." as label,
              ST_AsText(ST_Transform(".$geom.",$viewSridInput)) as geom,
              ST_Distance(pt.inputgeom, ".$geom.") AS distance,
              '$viewSridInput' as srid
            FROM ".$this->tableName."
            JOIN pt
            ON ST_Dwithin(pt.inputgeom, ".$geom.", ".$this->searchBuffer.")
            ORDER BY distance
            LIMIT 1";
            $result = $connection->query($queryString)->fetchAll();

            if (count($result)==0) {
                $messages = !$this->doesTableExist() ? 'Table does not exist' : 'Object not found';
                $result = array(
                    '0' => array(
                        'label' => null,
                        'geom' => $wkt,
                        'distance' => null,
                        'srid' =>  $viewSridInput,
                        'messages' => $messages
                    )
                );
            }
        }

        return $result;
    }

    /**
     * Get the geometry name as a string from the geometry table
     * @return string
     * @throws DBALException
     */
    private function getPointGeomFieldName(): string
    {
        $connection = $this->getConnection();

        $geom = $connection->query("SELECT DISTINCT f_geometry_column as geom
                                    FROM geometry_columns
                                    WHERE  f_table_schema LIKE '".$this->getSchemaName()."'
                                    AND f_table_name LIKE " . $connection->quote($this->tableName))->fetchColumn();

        return $geom ?? '';
    }

    /**
     * Get the SRID as string 'EPSG:XXX' from the geometry table
     * @return string
     * @throws DBALException
     */
    private function getPointGeomGrid(): string
    {
        $connection = $this->getConnection();

        $srid = $connection->query("SELECT DISTINCT srid
                                    FROM geometry_columns
                                    WHERE  f_table_schema LIKE '".$this->getSchemaName()."'
                                    AND f_table_name LIKE " . $connection->quote($this->tableName))->fetchColumn();

        return $srid ? 'EPSG:'.$srid : '';
    }

    /**
     * Returns all coordinates as wkt-arrayList
     * @return mixed
     */
    private function convertCoordToWkt(): array
    {
        $resultCoordPairs = [];

        foreach($this->coordinates as $key => $val){
            $wktName = $val['name'];
            $lonLat = implode(" ", $val["value"] );
            $resultCoordPairs[] = strtoupper($wktName). '('. $lonLat . ')';
        }
        return $resultCoordPairs;
    }

    /**
     * Check Table is exits by schema and table name
     * @return bool
     * @throws DBALException
     */
    private function doesTableExist(): bool
    {
        $connection = $this->connection;

        $query = "SELECT EXISTS (SELECT 1 FROM pg_tables WHERE schemaname = '".$this->getSchemaName()."' AND tablename = '$this->tableName')";
        $check = $connection->query($query)->fetchColumn();
        return !!$check;
    }

}
