<?php

namespace Mapbender\RoutingBundle\Component\RoutingDriver;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;

class PgRoutingDBInterface {

    /**
     * @var Connection $connection
     */
    private $connection;

    private $routingTable;
    private $nodesTable;

    private $epsg;
    private $srid;

    /**
     * PgRoutingDBInterface constructor.
     * @param Connection $connection
     * @param $waysTable
     * @param $verticesTable
     * @param $viewSrid
     * @throws Exception
     */

    private $temptableerouting = "temptablerouting";
    private $temptablenodes = "temptablenodes";

    private $street_name = "strname";

    public function __construct(Connection $connection, $routingTable, $nodesTable, $srid) {
        $this->connection = $connection;
        $this->routingTable = $routingTable;
        $this->nodesTable = $nodesTable;
        $this->srid = $srid;
        $this->epsg = "EPSG:".$srid;


        $this->dropTempTables();

        $this->createTempTable($this->routingTable,$this->temptableerouting);
        $this->createTempTable($this->nodesTable,$this->temptablenodes);

    }

    /**
     * create duplicate of node table to modify
     * @param string $origin
     * @param string $temp
     * @return string
     * @throws Exception
     */
    protected function createTempTable(string $origin,string $temp)
    {
        $db = $this->connection;
        $originq = $db->quoteIdentifier($origin);
        $tempq = $db->quoteIdentifier($temp);
        $db->query("CREATE TABLE $tempq  AS SELECT * FROM $originq");
    }



    /**
     * @param string $tableName
     * @return mixed
     */
    private function getPrimaryKeyOfTable(string $tableName)
    {
        $db = $this->connection;

        $query = "SELECT
                  a.attname
                FROM
                  pg_attribute a
                  JOIN (SELECT *, GENERATE_SUBSCRIPTS(indkey, 1) AS indkey_subscript FROM pg_index) AS i
                    ON
                      i.indisprimary
                      AND i.indrelid = a.attrelid
                      AND a.attnum = i.indkey[i.indkey_subscript]
                WHERE
                  a.attrelid = '$tableName'::regclass
                ORDER BY
                  i.indkey_subscript";

        return $db->fetchOne($query);
    }

    /**
     * Get the geometry name as a string from the geometry table
     * @param string $tableName
     * @return string
     * @throws Exception
     */
    protected function getGeomField(string $tableName)
    {
        $connection = $this->connection;

        $result = $connection->executeQuery('SELECT DISTINCT f_geometry_column as geom
                                    FROM geometry_columns
                                    WHERE f_table_name = ' . $connection->quote($tableName))->fetchOne();
        return $result ?: '';
    }


    /**
     * drop temporary used routing and node table
     * @throws Exception
     */
    public function dropTempTables()
    {
        $db = $this->connection;
        $tables = array($this->temptableerouting,$this->temptablenodes);
        foreach ($tables as &$table) {
            $table = $db->quoteIdentifier($table);
        }
        $tablesString = implode(', ', $tables);

        $db->query("DROP TABLE IF EXISTS $tablesString CASCADE")->fetchAll();
    }

    /**
     * Get the SRID as string 'EPSG:XXX' from the geometry table
     * @param $table
     * @param $withEPSG
     * @return string
     * @throws Exception
     */
    protected function getSrid(string $table, bool $withEPSG = false)
    {
        $result = $this->connection->executeQuery('SELECT DISTINCT srid
                                    FROM geometry_columns
                                    WHERE f_table_name = ' . $this->connection->quote($table))->fetchOne();
        return $withEPSG ? 'EPSG:' . $result : $result;
    }


    /**
     * create serial node Id of added waypoint
     * @param null $table
     * @return mixed
     * @throws Exception
     */
    public function getMaxId( $table = null)
    {
        $table = $table ?: $this->temptableerouting;
        $qTable = $this->connection->quoteIdentifier($table);

        $query = "SELECT max(gid) FROM $qTable";
        $result = $this->connection->executeQuery($query)->fetchOne();

        return $result;
    }

    private function adjustTemporaryRoutingTable($ewkt,$weightingColumn){
        // inserts poi as new node into routing table and updates previous geometry
        $db = $this->connection;

        $tempTableNodes = $db->quoteIdentifier($this->temptablenodes);
        $tempTableRouting = $db->quoteIdentifier($this->temptableerouting);

        $routingSrid = $this->getSrid($this->routingTable,false);

        $geom_string = "ST_PointFromText('" . $ewkt . "', 4326)";

        $queryUpdateRoutingTable =
            "WITH pid AS (SELECT max(id) AS new_pid FROM $tempTableNodes),
                point_data AS (SELECT * FROM $tempTableRouting ORDER BY ST_DISTANCE(ST_TRANSFORM($geom_string, $routingSrid), ST_TRANSFORM($tempTableRouting.geom, $routingSrid)) LIMIT 1),
                insert_data AS (SELECT target FROM $tempTableRouting WHERE gid = (SELECT gid FROM point_data)),
                fraction AS (SELECT ST_LineLocatePoint(ST_TRANSFORM((ST_DUMP(geom)).geom, $routingSrid), ST_TRANSFORM($geom_string, $routingSrid)) as fraction_value FROM point_data),
                source_query AS (SELECT fraction_value,
					CASE WHEN fraction_value > 0.5 THEN (SELECT new_pid FROM pid)
					ELSE (SELECT source from $tempTableRouting WHERE gid = (SELECT gid FROM point_data)) END AS source from fraction),
                target_query AS (SELECT fraction_value,
					CASE WHEN fraction_value > 0.5 THEN (SELECT target from $tempTableRouting WHERE gid = (SELECT gid FROM point_data))
					ELSE (SELECT new_pid FROM pid) END AS target from fraction),
				geom_query AS (SELECT ST_Transform(ST_Multi(ST_MakeLine(
                    (SELECT the_geom FROM $tempTableNodes WHERE id = (SELECT source FROM source_query)),
                    (SELECT the_geom FROM $tempTableNodes WHERE id = (SELECT target FROM target_query)))), $routingSrid) AS geom_value),
                source_update AS (SELECT fraction_value,
					CASE WHEN fraction_value > 0.5 THEN (SELECT source from $tempTableRouting WHERE gid = (SELECT gid FROM point_data))
					ELSE (SELECT new_pid FROM pid) END AS source from fraction),
                target_update AS (SELECT fraction_value,
					CASE WHEN fraction_value > 0.5 THEN (SELECT new_pid FROM pid)
					ELSE (SELECT target from $tempTableRouting WHERE gid = (SELECT gid FROM point_data)) END AS target from fraction),
                geom_update AS (SELECT ST_Transform(ST_Multi(ST_MakeLine(
                    (SELECT the_geom FROM $tempTableNodes WHERE id = (SELECT source FROM source_update)),
                    (SELECT the_geom FROM $tempTableNodes WHERE id = (SELECT target FROM target_update)))), $routingSrid) as update_geom_value),
                insert_query AS (INSERT INTO $tempTableRouting (gid, geom, $weightingColumn, source, target) VALUES
                    ((SELECT new_pid FROM pid),
                    (SELECT geom_value from geom_query),
                    (SELECT ST_Length(geom_value) FROM geom_query),
                    (SELECT source FROM source_query),
                    (SELECT target FROM target_query)) RETURNING *),
                update_query AS (UPDATE $tempTableRouting SET
                    source = (SELECT source FROM source_update),
                    target = (SELECT target FROM target_update),
                    geom = (SELECT update_geom_value FROM geom_update),
                    $weightingColumn = (SELECT ST_Length(update_geom_value) FROM geom_update)
                    WHERE gid = (SELECT gid FROM point_data) RETURNING *)
                SELECT * FROM pid";

        /* evaluation of result */
        $db->query($queryUpdateRoutingTable)->fetchAll();
    }

    private function adjustTemporaryNodeTable($ewkt){
        $db = $this->connection;

        $tempTableNodes = $db->quoteIdentifier($this->temptablenodes);
        $tempTableRouting = $db->quoteIdentifier($this->temptableerouting);

        $routingSrid = $this->getSrid($this->routingTable,false);

        $geom_string = "ST_PointFromText('" . $ewkt . "', 4326)";

        // inserts poi as new node into vertices table
        $queryUpdateNodeTable =
            "WITH point_data AS (SELECT * FROM $tempTableRouting ORDER BY ST_DISTANCE(ST_TRANSFORM($geom_string, $routingSrid), ST_TRANSFORM($tempTableRouting.geom, $routingSrid)) LIMIT 1),
                pid AS (SELECT max(gid)+1 as new_pid FROM $tempTableRouting)
            INSERT INTO $tempTableNodes (id, the_geom) VALUES
                ((SELECT -new_pid FROM pid), (SELECT ST_TRANSFORM($geom_string, $routingSrid))),
                ((SELECT new_pid FROm pid),
                (SELECT ST_LineInterpolatePoint(
                        ST_TRANSFORM((ST_DUMP(geom)).geom, $routingSrid),
                        ST_LineLocatePoint(ST_TRANSFORM((ST_DUMP(geom)).geom, $routingSrid), ST_TRANSFORM($geom_string, $routingSrid))) FROM point_data))";


        $db->query($queryUpdateNodeTable)->fetchAll();

    }
    public function adjustTemporaryTables($ewkt,$weightingColumn)
    {
        $this->adjustTemporaryNodeTable($ewkt);
        $this->adjustTemporaryRoutingTable($ewkt,$weightingColumn);
    }


    /**
     * @param array $nodeIdList
     * @param string $routeCostRow
     * @return array
     * @throws Exception
     */
    public function routeBetweenNodes(array $nodeIdList, string $routeCostRow) : array
    {
        $db = $this->connection;
        $directedGraph = false;
        $hasReverseCost = false;
        $directedGraph = $directedGraph ? 'TRUE' : 'FALSE'; // directed graph [true|false]
        $hasReverseCost = $hasReverseCost && $directedGraph ? 'TRUE' : 'FALSE'; // directed graph [true|false]

        $routingTableGeomField = $db->quoteIdentifier($this->getGeomField($this->temptableerouting));
        $idKey = $db->quoteIdentifier("gid");

        $routingSrid = $this->getSrid($this->routingTable,false);

        /* convert array to String-Array */
        $nodeIdListString = implode(",", $nodeIdList);

        $query = "
            WITH routing_query AS (SELECT
                    route.seq as orderId,
                    route.cost as distance,
                    $this->temptableerouting.$this->street_name as strname,
                    ST_AsEWKT(ST_TRANSFORM($this->temptableerouting.$routingTableGeomField, $routingSrid)) AS geom,
                    ST_AsGeoJSON(ST_Transform($this->temptableerouting.$routingTableGeomField, $routingSrid),15,0)::json As geomJson,
                    st_azimuth(lag(the_geom) over (order by route.seq asc), the_geom) as prevAzimuth,
				    st_azimuth(the_geom, lead(the_geom) over (order by route.seq asc)) as nextAzimuth,
				    the_geom as geom_node,
                    route.node as node,
                    lead(route.node) over (order by route.seq asc) as nextNode,
                    route.end_vid as endNodeId,
                    route.start_vid as startNodeId,
					route.edge as edge
                FROM
                    pgr_dijkstraVia (
                        'SELECT $idKey AS id, $this->street_name, source, target, $routeCostRow AS cost FROM $this->temptableerouting',
                        ARRAY[$nodeIdListString],
                        $directedGraph,
                        $hasReverseCost
                    ) AS route
                LEFT JOIN $this->temptableerouting ON route.edge = $this->temptableerouting.gid
                LEFT JOIN $this->temptablenodes ON $this->temptablenodes.id = route.node),
            startEnd_inst AS (SELECT *, ST_Azimuth((SELECT the_geom FROM $this->temptablenodes WHERE id = node*-1), geom_node) AS startEnd_value FROM routing_query),
            instruction AS (SELECT *,
                CASE
				    WHEN abs(nextAzimuth-lag(prevAzimuth) over (ORDER BY orderid)) > 3 AND abs(nextAzimuth-lag(prevAzimuth) over (ORDER BY orderid)) < 3.2 THEN (-98)
                    WHEN node = startNodeId THEN (0)
                    WHEN abs(nextAzimuth-prevAzimuth) < 0.3 THEN (0)
					WHEN abs(nextAzimuth-prevAzimuth) < 0.8 THEN
						CASE WHEN nextAzimuth-prevAzimuth > 0 THEN (1)
						ELSE (-1)
						END
					WHEN abs(nextAzimuth-prevAzimuth) < 1.8 THEN
						CASE WHEN nextAzimuth-prevAzimuth > 0 THEN (2)
						ELSE (-2)
						END
					WHEN nextAzimuth-prevAzimuth > 0 THEN (3)
					WHEN node = endNodeId AND orderid = (SELECT max(orderid) FROM routing_query) THEN (4)
					WHEN node = endNodeId AND edge <= 0 THEN (5)
					ELSE (-3)
				END AS all_sign FROM startEnd_inst),
			parts AS (SELECT *,
			        (SELECT orderid FROM instruction a WHERE
			            a.orderid >= b.orderid AND a.all_sign != b.all_sign OR
			            a.orderid >= b.orderid AND a.strname != b.strname ORDER BY a.orderid LIMIT 1 )
			        AS p FROM instruction b),
			ranges AS (SELECT *, first_value(orderid) over (PARTITION BY p ORDER BY orderid) AS start FROM parts),
			sum_query AS (SELECT sum(distance) over (PARTITION BY start ORDER BY orderid) AS sum_distance, * FROM ranges),
			filter_distance_query AS (SELECT *,
				CASE
				    WHEN lead(start) over (ORDER BY orderid) = start THEN NULL
					ELSE sum_distance
                END AS sum_filter_distance FROM sum_query),
			sign_query AS (SELECT *,
				CASE
				    WHEN sum_filter_distance IS NULL THEN NULL
					ELSE all_sign
                END AS sign FROM filter_distance_query)
			SELECT sum_filter_distance*1000 AS time, * FROM sign_query;";

        $results = $db->query($query)->fetchAll();

        return $results;
    }


    /**
     * @param array $geom
     * @return bool|string
     * @throws Exception
     */
    public function getResultGeom(array $geom)
    {
        $db = $this->connection;
        $wktGeomString = "'".implode("','", array_filter($geom, function($g) { return !!$g; }))."'";
        // get Multiline as GeoJSON
        $query = "SELECT ST_AsGeoJSON(ST_Union( ARRAY[$wktGeomString])) As geom";
        return $db->executeQuery($query)->fetchOne();
    }


    /**
     * get nearest point to given geometry
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getTransformedCoordinates()
    {
        $db = $this->connection;

        $routingSrid = $this->getSrid($this->routingTable,false);

        $query = "
              SELECT ST_X(ST_Transform(the_geom,$routingSrid)) as x, ST_Y(ST_Transform(the_geom,$routingSrid)) as y
              FROM $this->temptablenodes where id > (select max(id) from $this->nodesTable)
            ";

        return $db->fetchAllAssociative($query);
    }

}
