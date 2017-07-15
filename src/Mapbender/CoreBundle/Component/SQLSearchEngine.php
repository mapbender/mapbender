<?php

namespace Mapbender\CoreBundle\Component;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SQLSearchEngine
 *
 * @package Mapbender\CoreBundle\Component
 * @deprecated and will be removed in 3.0.7 release
 */
class SQLSearchEngine
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * SQLSearchEngine constructor.
     *
     * @param $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * SQL Autocomplete method
     *
     * @todo Make like search configurable (exact, left, right, both)
     * @todo Make case invariant configurable
     * @todo Limit results
     *
     * @param  array  $config     Search configuration
     * @param  String $key        Autocomplete field nme
     * @param  String $value      Autocomplete value
     * @param  Object $properties All form values
     * @param  String $srs        Current map SRS
     * @param  array  $extent     Current map extent
     * @return array              Autocomplete suggestions
     */
    public function autocomplete($config, $key, $value, $properties, $srs, $extent)
    {
        // First, get DBAL connection service, either given one or default one
        /** @var Connection $connection */
        $connection     = $this->getConnection($config);
        $qb             = $connection->createQueryBuilder();

        if (!array_key_exists($key, $config['form'])) {
            $key = '"' . $key . '"';
        }

        $distinct = false;
        if(array_key_exists('attr', $config['form'][$key]['options'])
            && array_key_exists('data-autocomplete-distinct', $config['form'][$key]['options']['attr'])
            && strtolower($config['form'][$key]['options']['attr']['data-autocomplete-distinct']) == 'on') {
            $distinct = true;
        }

        $keys = array($key);
        $values = array($value);
        if(array_key_exists('split', $config['form'][$key])) {
            $keys = $config['form'][$key]['split'];
            $values = explode(' ', $value);
        }

        // Build SELECT
        $select = implode(', ', array_map(function($attribute) {
            return 't.' . $attribute;
        }, $keys));

        if(array_key_exists('autocomplete-key', $config['form'][$key])) {
            $select .= ', t.' . $config['form'][$key]['autocomplete-key'];
        }

        $qb->select($distinct ? 'DISTINCT ' . $select : $select);

        // Add FROM
        $qb->from($config['class_options']['relation'], 't');

        // Build WHERE condition
        $cond = $qb->expr()->andx();
        $params = array();
        for($i = 0; $i < count($keys); $i++) {
            // @todo: Platform independency (::varchar, lower)
            $cond->add($qb->expr()->like('LOWER(t.' . $keys[$i] . '::varchar)', '?'));
            $params[] = '%' . (count($values) > $i ? mb_strtolower($values[$i], 'UTF-8') : '') . '%';
        }

        $logger = $this->container->get('logger');

        if(array_key_exists('attr', $config['form'][$key]['options'])
            && array_key_exists('data-autocomplete-using', $config['form'][$key]['options']['attr'])) {
            $using = explode(',', $config['form'][$key]['options']['attr']['data-autocomplete-using']);
            array_walk($using, function($key) use ($properties, $logger, $cond, $qb, &$params) {
                if(property_exists($properties, $key)) {
                    $value = $properties->$key;
                    if(!$value) {
                        return;
                    }
                    $cond->add($qb->expr()->eq('t.' . $key, '?'));
                    $params[] = $value;
                } else {
                    $logger->warn('Key "' . $key . '" for autocomplete-using does not exist in data.');
                }
            });
        }

        $qb->where($cond);
        $qb->orderBy('t.' . $key, 'ASC');

        // Create prepared statement and execute
        $stmt = $connection->executeQuery($qb->getSql(), $params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        array_walk($rows, function (&$row) use ($key, $keys, $config) {
            $value = array();
            foreach ($keys as $k) {
                if (!array_key_exists($k, $row)) {
                    $k = trim($k, '"');
                }
                $value[] = $row[$k];
            }

            if(array_key_exists('autocomplete-key', $config['form'][$key])) {
                $row = array(
                    'key' => $row[$config['form'][$key]['autocomplete-key']],
                    'value' => implode(' ', $value));
            } else {
                $row = array(
                    'value' => implode(' ', $value)
                );
            }
        });
        return $rows;
    }

    /**
     * Actual SQL search method
     *
     * @todo Make like search configurable (exact, left, right, both)
     * @todo Make case invariant configurable
     * @todo Paging
     *
     * @param  array  $config Search configuration
     * @param  array  $data   Form data
     * @param  string $srs    Search extent SRS
     * @param  array  $extent Search extent
     * @return array         Search results
     */
    public function search($config, $data, $srs, $extent)
    {

        // First, get DBAL connection service, either given one or default one

        $connection     = $this->getConnection($config);
        $qb             = $connection->createQueryBuilder();

        // Build SELECT
        $select = implode(', ', array_map(function($attribute)
                        {
                            return 't.' . $attribute;
                        }, $config['class_options']['attributes']));


        // Add geometry to SELECT
        // // @todo: Platform independency (ST_AsGeoJSON)
        $select .= ', ST_AsGeoJSON(' . $config['class_options']['geometry_attribute'] . ') as geom';

        $qb->select($select);

        // Add FROM
        $qb->from($config['class_options']['relation'], 't');

        // Build WHERE condition
        $cond = $qb->expr()->andx();
        $params = array();

        // This function switches by compare configuration (exact, like, ilike, ...)
        $createExpr = function($key, $value) use ($cond, $config, $connection, &$params, $qb) {
            if (array_key_exists($key, $config['form'])) {
                $cfg = $config['form'][$key];
            } else {
                $cfg = $config['form']['"' . $key . '"'];
            }

            $compare = array_key_exists('compare', $cfg) ? $cfg['compare'] : null;
            switch($compare) {
                case 'exact':
                    $cond->add($qb->expr()->eq('t.' . $key, ':' . $key));
                    $params[$key] = $value;
                    break;
                case 'iexact':
                $cond->add($qb->expr()->eq('LOWER(t.' . $key . ')', 'LOWER(:' .$key . ')'));
                    $params[$key] = $value;
                    break;
                case 'like':
                case 'like-left':
                case 'like-right':
                    $cond->add($qb->expr()->like('t.' . $key, ':' . $key));

                    // First, asume two-sided search
                    $prefix = '%';
                    $suffix = '%';
                    $op = explode('-', $compare);
                    if(2 === count($op) && 'left' === $op[1]) {
                        // For left-sided search remove suffix
                        $suffix = '';
                    }
                    if(2 === count($op) && 'right' === $op[1]) {
                        // For right-sided search remove prefix
                        $prefix = '';
                    }
                    $params[$key] = $prefix . $value . $suffix;
                    break;
                case 'ilike':
                case 'ilike-left':
                case 'ilike-right':
                default:
                    // First, asume two-sided search
                    $prefix = '%';
                    $suffix = '%';
                    if(is_string($compare)) {
                        $op = explode('-', $compare);
                        if(2 === count($op) && 'left' === $op[1]) {
                            // For left-sided search remove suffix
                            $suffix = '';
                        }
                        if(2 === count($op) && 'right' === $op[1]) {
                            // For right-sided search remove prefix
                            $prefix = '';
                        }
                    }

                    if($connection->getDatabasePlatform() instanceof PostgreSqlPlatform) {
                        $cond->add($qb->expr()->comparison('t.' . $key, 'ILIKE', ':' . $key));
                    } else {
                        $cond->add($qb->expr()->like('LOWER(t.' . $key . ')', 'LOWER(:' . $key . ')'));
                    }

                    $params[$key] = $prefix . $value . $suffix;
            }
        };

        foreach($data['form'] as $key => $value)
        {
            if(null === $value) {
                continue;
            }

            if (!array_key_exists($key, $config['form'])) {
                $cfg = $config['form']['"' . $key . '"'];
            } else {
                $cfg = $config['form'][$key];
            }
            if(array_key_exists($key, $data['autocomplete_keys']))
            {
                // Autocomplete value given, match to configured attribute
                $cond->add($qb->expr()->eq(
                    't.' . $cfg['autocomplete-key'], $data['autocomplete_keys'][$key]));
            } elseif (array_key_exists('split', $cfg)) {
                // Split
                $keys = $cfg['split'];
                $values = explode(' ', $value);
                for($i = 0; $i < count($keys); $i++)
                {
                    $createExpr($keys[$i], $value);
                }
            } else {
                $createExpr($key, $value);
            }
        }

        if(isset($config['class_options']['id'])) {
            $cond->add($qb->expr()->comparison('t.' . $config['class_options']['id'], '=', ':' . $key));
        }

        $qb->where($cond);


        /*
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         * #######################################################
         */

        // REVIEW THIS PLEASE! looks like i shouldn't have done this!!
        // Possible SQL injection?
        if(isset($config['class_options']['order_by'])) {
            $qb->orderBy($config['class_options']['order_by']);
        }
        // REVIEW THIS PLEASE! LOOKS like i shouldn't have done this!!


        //echo $qb->getSql();
        //exit;

        // Create prepared statement and execute
        $stmt = $connection->executeQuery($qb->getSql(), $params);
        $rows = $stmt->fetchAll();

        // Rewrite rows as GeoJSON features
        array_walk($rows, function(&$row)
                {
                    $feature = array(
                        'type' => 'Feature',
                        'properties' => $row,
                        'geometry' => json_decode($row['geom'])
                    );
                    unset($feature['properties']['geom']);
                    $row = $feature;
                });

        return $rows;
    }

    /**
     * @param $config
     * @return   Connection $connection
     */
    protected function getConnection($config)
    {
        $connectionName = $config['class_options']['connection'] ?: 'default';
        return $this->container->get('doctrine.dbal.' . $connectionName . '_connection');
    }

}
