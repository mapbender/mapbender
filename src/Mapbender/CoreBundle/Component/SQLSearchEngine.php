<?php

namespace Mapbender\CoreBundle\Component;

use Doctrine\DBAL\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SQLSearchEngine
 *
 * @package Mapbender\CoreBundle\Component
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

        $qb->select($distinct ? 'DISTINCT ' . $select : $select);

        // Add FROM
        $qb->from($config['class_options']['relation'], 't');

        // Build WHERE condition
        $cond = $qb->expr()->andX();
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
                    $value = $properties->{$key};
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
        $stmt = $connection->executeQuery($qb->getSQL(), $params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        array_walk($rows, function (&$row) use ($key, $keys, $config) {
            $value = array();
            foreach ($keys as $k) {
                if (!array_key_exists($k, $row)) {
                    $k = trim($k, '"');
                }
                $value[] = $row[$k];
            }
            $row = array(
                'value' => implode(',', $value),
            );
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

        // This function switches by compare configuration (exact, like, ilike, ...)
        $createExpr = function($key, $value) use ($config, $qb) {
            if (array_key_exists($key, $config['form'])) {
                $cfg = $config['form'][$key];
            } else {
                $cfg = $config['form']['"' . $key . '"'];
            }

            $compare = array_key_exists('compare', $cfg) ? $cfg['compare'] : null;

            switch ($compare) {
                case 'exact':
                case 'like':
                case 'like-left':
                case 'like-right':
                    $caseInsensitive = false;
                    break;
                default:
                case 'iexact':
                case 'ilike':
                case 'ilike-left':
                case 'ilike-right':
                    $caseInsensitive = true;
                    break;
            }
            switch ($compare) {
                default:
                case 'ilike':
                case 'like':
                    $patternPrefix = '%';
                    $patternSuffix = '%';
                    break;
                case 'ilike-left':
                case 'like-left':
                    $patternPrefix = '%';
                    $patternSuffix = '';
                    break;
                case 'ilike-right':
                case 'like-right':
                    $patternPrefix = '';
                    $patternSuffix = '%';
                    break;
                case 'exact':
                case 'iexact':
                    $patternPrefix = '';
                    $patternSuffix = '';
                    break;
            }
            $matchValue = strtr($value, array(
                '%' => '\%',
                '_' => '\_',
            ));
            $matchValue = "{$patternPrefix}{$matchValue}{$patternSuffix}";
            $placeHolder = $qb->createNamedParameter($matchValue);
            $referenceExpression = "t.{$key}";
            $matchExpression = $placeHolder;
            if ($caseInsensitive) {
                $referenceExpression = "LOWER({$referenceExpression})";
                $matchExpression = "LOWER({$matchExpression})";
            }
            if (!$patternPrefix && !$patternSuffix) {
                return $qb->expr()->eq($referenceExpression, $matchExpression);
            } else {
                return $qb->expr()->like($referenceExpression, $matchExpression);
            }
        };

        $cond = $qb->expr()->andX();
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
            if (array_key_exists('split', $cfg)) {
                // Split
                $keys = $cfg['split'];
                $values = explode(' ', $value);
                for($i = 0; $i < count($keys); $i++)
                {
                    $cond->add($createExpr($keys[$i], $value));
                }
            } else {
                $cond->add($createExpr($key, $value));
            }
        }
        $qb->where($cond);

        // Create prepared statement and execute
        $stmt = $connection->executeQuery($qb->getSQL(), $qb->getParameters());
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
