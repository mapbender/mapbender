<?php

namespace Mapbender\CoreBundle\Component;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Mapbender\CoreBundle\Utils\ArrayUtil;
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
     * @param  string[] $properties All form values
     * @param  String $srs        Current map SRS
     * @param  array  $extent     Current map extent
     * @return array              Autocomplete suggestions
     */
    public function autocomplete($config, $key, $value, $properties, $srs, $extent)
    {
        if (is_object($properties)) {
            $properties = get_object_vars($properties);
        }
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
        $cond->add($this->getTextMatchExpression($key, $value, 'ilike', $qb));
        if ($srs && $extent && !empty($config['class_options']['geometry_attribute'])) {
            $geomColumn = 't.' . $connection->quoteIdentifier(trim($config['class_options']['geometry_attribute'], '"'));
            $cond->add($this->getBoundsExpression($qb, $geomColumn, $extent, $srs));
        }

        $logger = $this->container->get('logger');
        if (!empty($fieldConfig['options']['attr']['data-autocomplete-using'])) {
            $otherProps = explode(',', $fieldConfig['options']['attr']['data-autocomplete-using']);
            foreach ($otherProps as $otherProp) {
                if (!empty($properties[$otherProp])) {
                    $cond->add($this->getTextMatchExpression($otherProp, $properties[$otherProp], 'ilike-right', $qb));
                } else {
                    $logger->warn('Key "' . $otherProp . '" for autocomplete-using does not exist in data.');
                }
            }
        }

        $qb->where($cond);
        $qb->orderBy('t.' . $key, 'ASC');

        $stmt = $qb->execute();
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

        $options = $config['class_options'];
        $connection     = $this->getConnection($config);
        $qb             = $connection->createQueryBuilder();
        $selectExpressions = array();
        foreach ($options['attributes'] as $columName) {
            $selectExpressions[] = 't.' . $connection->quoteIdentifier(trim($columName, '"'));
        }
        // add geometry
        $geomColumn = 't.' . $connection->quoteIdentifier(trim($options['geometry_attribute'], '"'));
        $srsId = $this->srsIdFromName($srs);
        $srsParamPlaceholder = $qb->createNamedParameter($srsId);
        $geomTransformed = "ST_Transform({$geomColumn}, {$srsParamPlaceholder}::int)";
        $selectExpressions[] = "ST_AsGeoJSON({$geomTransformed}) as geom";

        $qb->select(implode(', ', $selectExpressions));
        // Add FROM
        $qb->from($config['class_options']['relation'], 't');

        $cond = $qb->expr()->andX();
        foreach($data['form'] as $key => $value) {
            if (!$value) {
                continue;
            }
            $fieldConfig = $this->getFormFieldConfig($config, $key);
            $matchMode = ArrayUtil::getDefault($fieldConfig, 'compare', 'ilike');
            $cond->add($this->getTextMatchExpression($key, $value, $matchMode, $qb));
        }
        if ($srs && $extent) {
            $cond->add($this->getBoundsExpression($qb, $geomColumn, $extent, $srs));
        }

        $qb->where($cond);

        $stmt = $qb->execute();
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
            if (!$row['geom']) {
                continue;
            }
            $geometry = @json_decode($row['geom'], true);
            if (!$geometry) {
                continue;
            }
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
     * @param string $mode
     * @param QueryBuilder $qb
     * @return mixed
     */
    protected function getTextMatchExpression($key, $value, $mode, $qb) {

        switch ($mode) {
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
        switch ($mode) {
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
     * @param QueryBuilder $qb
     * @param string $geomReference
     * @param float[] $extent 4 values left / bottom / right / top
     * @param string $srsName
     * @return string
     */
    protected function getBoundsExpression(QueryBuilder $qb, $geomReference, $extent, $srsName)
    {
        $boxPoints = array(
            'ST_Point(' . $qb->createNamedParameter($extent[0]) . ', ' . $qb->createNamedParameter($extent[1]) . ')',
            'ST_Point(' . $qb->createNamedParameter($extent[2]) . ', ' . $qb->createNamedParameter($extent[3]) . ')',
        );
        $srsId = $this->srsIdFromName($srsName);
        $box = 'ST_SetSRID(ST_MakeBox2D(' . implode(', ', $boxPoints) . '), ' . $qb->createNamedParameter($srsId) . ')';
        $transformedBox = "ST_Transform({$box}, ST_Srid({$geomReference}))";
        return "{$transformedBox} && {$geomReference}";
    }

    /**
     * Strips namespace prefix from given $srsName and returns the numeric srs id.
     * Only supports 'EPSG:' namespace prefix. If $srsName already is a plain number,
     * return it cast to integer but otherwise unchanged.
     *
     * @param $srsName
     * @return int
     */
    protected function srsIdFromName($srsName)
    {
        $parts = explode(':', $srsName);
        if (count($parts) === 1 && (strval(intval($parts[0])) === strval($parts[0])) && $parts[0]) {
            return intval($parts[0]);
        }
        if (count($parts) !== 2 || $parts[0] !== 'EPSG') {
            throw new \InvalidArgumentException("Unsupported srs name " . print_r($srsName, true));
        }
        return intval($parts[1]);
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
