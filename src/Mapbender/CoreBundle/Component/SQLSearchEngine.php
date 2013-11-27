<?php

namespace Mapbender\CoreBundle\Component;

class SQLSearchEngine
{

    protected $container;

    public function __construct($container)
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
     * @param  Array  $config     Search configuration
     * @param  String $key        Autocomplete field nme
     * @param  String $value      Autocomplete value
     * @param  Object $properties All form values
     * @param  String $srs        Current map SRS
     * @param  Array  $extent     Current map extent
     * @return Array              Autocomplete suggestions
     */
    public function autocomplete($config, $key, $value, $properties, $srs, $extent)
    {
        // First, get DBAL connection service, either given one or default one
        $connection = $config['class_options']['connection'] ? : 'default';
        $connection = $this->container->get('doctrine.dbal.' . $connection . '_connection');
        $qb = $connection->createQueryBuilder();

        $keys = array($key);
        $values = array($value);
        if(array_key_exists('split', $config['form'][$key]))
        {
            $keys = $config['form'][$key]['split'];
            $values = explode(' ', $value);
        }

        // Build SELECT
        $select = implode(', ', array_map(function($attribute)
                        {
                            return 't.' . $attribute;
                        }, $keys));
        if(array_key_exists('autocomplete-key', $config['form'][$key]))
        {
            $select .= ', t.' . $config['form'][$key]['autocomplete-key'];
        }
        $qb->select($select);

        // Add FROM
        $qb->from($config['class_options']['relation'], 't');

        // Build WHERE condition
        $cond = $qb->expr()->andx();
        $params = array();
        for($i = 0; $i < count($keys); $i++)
        {
            // @todo: Platform independency (::varchar, lower)
            $cond->add($qb->expr()->like('LOWER(t.' . $keys[$i] . '::varchar)', '?'));
            $params[] = '%' . (count($values) > $i ? strtolower($values[$i]) : '') . '%';
        }
        $qb->where($cond);

        // Create prepared statement and execute
        $this->container->get('logger')->info('SQL: ' . $qb->getSql() . '; Params: ' . print_r($params, true));
        $stmt = $connection->executeQuery($qb->getSql(), $params);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        array_walk($rows, function(&$row) use ($key, $keys, $config)
                {
                    $value = array();
                    foreach($keys as $k)
                    {
                        $value[] = $row[$k];
                    }

                    if(array_key_exists('autocomplete-key', $config['form'][$key]))
                    {
                        $row = array(
                            'key' => $row[$config['form'][$key]['autocomplete-key']],
                            'value' => implode(' ', $value));
                    } else
                    {
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
     * @param  Array $config Search configuration
     * @param  Array $data   Form data
     * @param  String $srs   Search extent SRS
     * @param  Array $extent Search extent
     * @return Array         Search results
     */
    public function search($config, $data, $srs, $extent)
    {
        // First, get DBAL connection service, either given one or default one
        $connection = $config['class_options']['connection'] ? : 'default';
        $connection = $this->container->get('doctrine.dbal.' . $connection . '_connection');
        $qb = $connection->createQueryBuilder();

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

        // This function switches by value type (int, char...)
        $createExpr = function($key, $value) use ($cond, $qb, $params) {
            if(is_numeric($value)) {
                $cond->add($qb->expr()->eq('t.'. $key, '?'));
                $params[] = $value;
            } else {
                $cond->add($qb->expr()->like('LOWER(t.' . $key . ')::varchar', '?'));
                $params[] = '%' . $value . '%';
            }
        };

        foreach($data['form'] as $key => $value)
        {
            if(array_key_exists($key, $data['autocomplete_keys']))
            {
                // Autocomplete value given, match to configured attribute
                $cond->add($qb->expr()->eq(
                                't.' . $config['form'][$key]['autocomplete-key'], $data['autocomplete_keys'][$key]));
            } else if(array_key_exists('split', $config['form'][$key]))
            {
                // Split
                $keys = $config['form'][$key]['split'];
                $values = explode(' ', $value);
                for($i = 0; $i < count($keys); $i++)
                {
                    $createExpr($keys[$i], $value);
                }
            } else
            {
                $createExpr($key, $value);
            }
        }
        $qb->where($cond);

        // Create prepared statement and execute
        $this->container->get('logger')->info('SQL: ' . $qb->getSql() . '; Params: ' . print_r($params, true));
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

}
