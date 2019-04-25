<?php

namespace Mapbender\CoreBundle\Component;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
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
        $fieldConfig = $this->getFormFieldConfig($config, $key);

        $qb->select("DISTINCT t.{$key}");

        // Add FROM
        $qb->from($config['class_options']['relation'], 't');

        // Build WHERE condition
        $cond = $qb->expr()->andX();
        $params = array('%' . $value . '%');
        // @todo: Platform independency (::varchar, lower)
        $cond->add($qb->expr()->like('LOWER(t.' . $key . '::varchar)', 'LOWER(?)'));

        $logger = $this->container->get('logger');

        if(array_key_exists('attr', $fieldConfig['options'])
            && array_key_exists('data-autocomplete-using', $fieldConfig['options']['attr'])) {
            $using = explode(',', $fieldConfig['options']['attr']['data-autocomplete-using']);
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

        $stmt = $connection->executeQuery($qb->getSQL(), $params);
        $dataOut = array();
        foreach ($stmt as $row) {
            if (!array_key_exists($key, $row)) {
                $value = $row[trim($key, '"')];
            } else {
                $value = $row[$key];
            }
            $dataOut[] = array(
                'value' => $value,
            );
        }
        return $dataOut;
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

        $cond = $qb->expr()->andX();
        foreach($data['form'] as $key => $value) {
            if (!$value) {
                continue;
            }
            $cond->add($this->getTextMatchExpression($key, $value, $config, $qb));
        }
        $qb->where($cond);

        // Create prepared statement and execute
        $stmt = $connection->executeQuery($qb->getSQL(), $qb->getParameters());
        return $this->rowsToGeoJson($stmt);
    }

    /**
     * @param array|\Traversable $rows
     * @return array
     */
    protected static function rowsToGeoJson($rows)
    {
        $features = array();
        foreach ($rows as $row) {
            $geometry = json_decode($row['geom']);
            unset($row['geom']);
            $features[] = array(
                'type' => 'Feature',
                'properties' => $row,
                'geometry' => $geometry,
            );
        }
        return $features;
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

    /**
     * @param string $key
     * @param mixed $value
     * @param array $config
     * @param QueryBuilder $qb
     * @return mixed
     */
    protected function getTextMatchExpression($key, $value, $config, $qb) {
        $cfg = $this->getFormFieldConfig($config, $key);

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
    }

    /**
     * @param array $config
     * @param string $key
     * @return array
     */
    protected static function getFormFieldConfig($config, $key)
    {
        if (!array_key_exists($key, $config['form'])) {
            return $config['form']['"' . $key . '"'];
        } else {
            return $config['form'][$key];
        }
    }
}
