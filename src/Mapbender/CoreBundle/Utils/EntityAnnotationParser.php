<?php
namespace Mapbender\CoreBundle\Utils;

/**
 * Entity annotation parser
 *
 * @author Andriy Oblivantsev
 * @author Paul Schmidt
 */
class EntityAnnotationParser
{
    const GET = EntityUtil::GET;
    const SET = EntityUtil::SET;
    const HAS = EntityUtil::HAS;
    const IS = EntityUtil::IS;
    const GETTER = EntityUtil::GETTER;
    const SETTER = EntityUtil::SETTER;
    const HAS_METHOD = EntityUtil::HAS_METHOD;
    const IS_METHOD = EntityUtil::IS_METHOD;
    const COLUMN = 'Column';
    const NAME = 'name';
    const JOIN_COLUMN = 'JoinColumn';

    /**
     * @param $className
     * @param bool $onlyAnnotation
     *
     * @return array
     */
    public static function parseFieldsAnnotations($className, $onlyAnnotation = true)
    {
        $reflect = new \ReflectionClass($className);
        $fields = array();
        $methodNames = array();

        foreach ($reflect->getMethods() as $method) {
            $methodNames[] = $method->getName();
        }

        // get all properties
        foreach ($reflect->getProperties() as $property) {
            $annotations = array();

            // get property annotations
            foreach (self::getAnnotations($property->getDocComment()) as $annotation) {

                // match only orm annotations only
                if (preg_match('/^ORM\\\(.+)/s', $annotation, $matches) || preg_match('/^Assert\\\(.+)/s', $annotation,
                                                                                      $matches)) {
                    $matches = preg_split('/\(/', $matches[1]);
                    $key = $matches[0];
                    // if matched annotation has some values, parse and add to value array
                    $annotations[$key] = isset($matches[1]) ? self::getAnnotationGroupedValues(
                            preg_replace('/\)$/', '', $matches[1])
                        ) : true;
                }
            }

            $fieldName = $property->getName();
            $methodHash = "";
            foreach (preg_split("/_/", $fieldName) as $chunk) {
                $chunk = ucwords($chunk);
                $methodHash .= $chunk;
            }
            if ($annotations || !$onlyAnnotation) {
                $fieldProperties = $annotations ?: array();
                foreach ($methodNames as $methodName) {
                    switch ($methodName) {
                        case self::GET . $methodHash:
                            $fieldProperties[self::GETTER] = $methodName;
                            break;
                        case self::SET . $methodHash:
                            $fieldProperties[self::SETTER] = $methodName;
                            break;
                        case self::HAS . $methodHash:
                            $fieldProperties[self::HAS_METHOD] = $methodName;
                            break;
                        case self::IS . $methodHash:
                            $fieldProperties[self::IS_METHOD] = $methodName;
                            break;
                    }
                }

                if (!isset($fieldProperties[self::GETTER])) {
                    // try to find alternative getter
                    if (isset($fieldProperties[self::HAS_METHOD])) {
                        $fieldProperties[self::GETTER] = $fieldProperties[self::HAS_METHOD];
                    } elseif (isset($annotations[self::IS_METHOD])) {
                        $fieldProperties[self::GETTER] = $fieldProperties[self::IS_METHOD];
                    }
                }
                if ($annotations) {
                    if (isset($fieldProperties[self::COLUMN]) && isset($fieldProperties[self::COLUMN][self::NAME])) {
                        $fieldProperties[self::NAME] = strtolower($fieldProperties[self::COLUMN][self::NAME]);
                    } elseif (isset($fieldProperties[self::JOIN_COLUMN]) && isset($fieldProperties[self::JOIN_COLUMN][self::NAME])) {
                        $fieldProperties[self::NAME] = strtolower($fieldProperties[self::JOIN_COLUMN][self::NAME]);
                    }
                }
                $fields[$fieldName] = $fieldProperties;
            }
        }
        return $fields;
    }

    /**
     * @param $text
     *
     * @return array
     */
    public static function getAnnotations($text)
    {
        preg_match_all('/@(.*?)\n/s', $text, $matches, PREG_SET_ORDER);
        $results = array();
        foreach ($matches as $match) {
            $results[] = $match[1];
        }
        return $results;
    }

    /**
     * @param $string
     *
     * @internal param $matches
     *
     * @return array
     */
    public static function getAnnotationGroupedValues($string)
    {
        $values = array();
        foreach (preg_split('/["\']?,\s*["\']?/', $string) as $result) {
            preg_match('/^(\w+)\=[\'"]?(.+?)[\'"]?$/', $result, $matches);
            if (!isset($matches[1])) {
                continue;
            }
            $values[$matches[1]] = $matches[2];
        }
        return $values;
    }

}
