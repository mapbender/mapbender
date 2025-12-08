<?php

namespace Mapbender\CoreBundle\Component;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Monolog\Logger;

class SQLSearchEngine
{
    public function __construct(
        protected Logger          $logger,
        protected ManagerRegistry $registry)
    {
    }

    /**
     * SQL Autocomplete method
     *
     * @param array $config Search configuration
     * @param String $key Autocomplete field nme
     * @param String $value Autocomplete value
     * @param string[] $properties All form values
     * @param String $srs Current map SRS
     * @param array $extent Current map extent
     * @return array              Autocomplete suggestions
     * @todo Make case invariant configurable
     * @todo Limit results
     *
     */
    public function autocomplete(array $config, string $key, string $value, array $properties, string $srs, array $extent): array
    {
        $connection = $this->getConnection($config);
        $qb = $connection->createQueryBuilder();
        $fieldConfig = $this->getFormFieldConfig($config, $key);

        $qb->select("DISTINCT t.{$connection->quoteIdentifier($key)}");

        // Add FROM
        $qb->from($config['class_options']['relation'], 't');

        // Build WHERE condition
        $qb->where($this->getMatchExpression($connection, $key, $value, 'ilike', $qb));
        if ($srs && $extent && !empty($config['class_options']['geometry_attribute'])) {
            $geomColumn = 't.' . $connection->quoteIdentifier(trim($config['class_options']['geometry_attribute'], '"'));
            $qb->andWhere($this->getBoundsExpression($qb, $geomColumn, $extent, $srs, $config));
        }

        if (!empty($fieldConfig['options']['attr']['data-autocomplete-using'])) {
            $otherProps = explode(',', $fieldConfig['options']['attr']['data-autocomplete-using']);
            foreach ($otherProps as $otherProp) {
                if (strlen($properties[$otherProp] ?? null)) {
                    $qb->andWhere($this->getMatchExpression($connection, $otherProp, $properties[$otherProp], 'ilike-right', $qb));
                } else {
                    $this->logger->warning('Key "' . $otherProp . '" for autocomplete-using does not exist in data.');
                }
            }
        }

        $qb->orderBy('t.' . $connection->quoteIdentifier($key), 'ASC');

        $stmt = $qb->executeQuery()->fetchAllAssociative();
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
     * @param array $config Search configuration
     * @param array $data Form data
     * @param string $srs Search extent SRS
     * @param array $extent Search extent
     * @return array         Search results
     * @todo Paging
     */
    public function search(array $config, array $data, string $srs, array $extent): array
    {
        $options = $config['class_options'];
        $connection = $this->getConnection($config);
        $qb = $connection->createQueryBuilder();
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

        foreach ($data['form'] as $key => $value) {
            if (!$value && !is_numeric($value)) {
                continue;
            }
            $fieldConfig = $this->getFormFieldConfig($config, $key);
            $matchMode = ArrayUtil::getDefault($fieldConfig, 'compare', 'ilike');
            $qb->andWhere($this->getMatchExpression($connection, $key, $value, $matchMode, $qb));
        }
        if ($srs && $extent) {
            $qb->andWhere($this->getBoundsExpression($qb, $geomColumn, $extent, $srs, $config));
        }

        $stmt = $qb->executeQuery()->fetchAllAssociative();
        return $this->rowsToGeoJson($stmt);
    }

    protected static function rowsToGeoJson(iterable $rows): array
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

    protected function getConnection($config): Connection
    {
        $connectionName = $config['class_options']['connection'] ?: 'default';
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->registry->getConnection($connectionName);
    }

    protected function getMatchExpression(Connection $connection, string $key, $value, string $mode, QueryBuilder $qb): string
    {
        $referenceExpression = "t." . $connection->quoteIdentifier($key);
        $matchValue = $this->prepareValue($value, $mode);

        $parameterType = match (true) {
            is_string($value) => ParameterType::STRING,
            is_numeric($value) => ParameterType::INTEGER,
            default => ParameterType::STRING
        };
        $matchExpression = $qb->createNamedParameter($matchValue, $parameterType);

        $ignoreCase = match ($mode) {
            'iexact', 'ilike', 'ilike-left', 'ilike-right' => true,
            default => false,
        };

        if ($ignoreCase) {
            $referenceExpression = "LOWER($referenceExpression)";
            $matchExpression = "LOWER($matchExpression)";
        }

        return $this->getMatchExpressionForMode($mode, $qb, $referenceExpression, $matchExpression);
    }

    protected function getMatchExpressionForMode(string $mode, QueryBuilder $qb, string $referenceExpression, string $matchExpression): string
    {
        return match ($mode) {
            'greater' => $qb->expr()->gt($referenceExpression, $matchExpression),
            'greater-equal' => $qb->expr()->gte($referenceExpression, $matchExpression),
            'lower',  => $qb->expr()->lt($referenceExpression, $matchExpression),
            'lower-equal' => $qb->expr()->lte($referenceExpression, $matchExpression),
            'ilike', 'like', 'ilike-left', 'like-left', 'ilike-right', 'like-right' => $qb->expr()->like($referenceExpression, $matchExpression),
            'not' => $qb->expr()->neq($referenceExpression, $matchExpression),
            default => $qb->expr()->eq($referenceExpression, $matchExpression),
        };
    }

    protected function prepareValue(mixed $value, string $mode): mixed
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            $patternPrefix = match ($mode) {
                'ilike', 'like', 'ilike-left', 'like-left' => '%',
                default => '',
            };
            $patternSuffix = match ($mode) {
                'ilike', 'like', 'ilike-right', 'like-right' => '%',
                default => '',
            };

            $value = strtr($value, array(
                '%' => '\%',
                '_' => '\_',
            ));
            return "$patternPrefix$value$patternSuffix";
        }
        return $value;
    }

    /**
     * @param float[] $extent 4 values left / bottom / right / top
     */
    protected function getBoundsExpression(QueryBuilder $qb, string $geomReference, array $extent, string $srsName, array $config): string
    {
        // per default, the map extent is compared to a feature by converting both to EPSG:4326 (WGS84).
        // comparing in a non-global SRS (like UTM32) can result in errors when comparing it to an extent defined
        // in a global CRS. This may reduce performance, so the transformation to EPSG:4326 can be disabled
        // by passing noTransform:true to class_options
        $noTransform = isset($config['class_options']['noTransform']) && $config['class_options']['noTransform'];

        $boxPoints = array(
            'ST_Point(' . $qb->createNamedParameter($extent[0]) . ', ' . $qb->createNamedParameter($extent[1]) . ')',
            'ST_Point(' . $qb->createNamedParameter($extent[2]) . ', ' . $qb->createNamedParameter($extent[3]) . ')',
        );
        $srsId = $this->srsIdFromName($srsName);
        $box = 'ST_SetSRID(ST_MakeBox2D(' . implode(', ', $boxPoints) . '), ' . $qb->createNamedParameter($srsId) . ')';
        $transformedBox = $noTransform ? "ST_Transform({$box}, ST_Srid({$geomReference}))" : "ST_Transform($box, 4326)";
        return $noTransform ? "$transformedBox && $geomReference" : "$transformedBox && ST_Transform($geomReference, 4326)";
    }

    /**
     * Strips namespace prefix from given $srsName and returns the numeric srs id.
     * Only supports 'EPSG:' namespace prefix. If $srsName already is a plain number,
     * return it cast to integer but otherwise unchanged.
     */
    protected function srsIdFromName($srsName): int
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

    protected static function getFormFieldConfig(array $config, string $key): array
    {
        if (!array_key_exists($key, $config['form'])) {
            return $config['form']['"' . $key . '"'];
        } else {
            return $config['form'][$key];
        }
    }
}
