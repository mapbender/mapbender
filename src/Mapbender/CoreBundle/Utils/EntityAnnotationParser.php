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
     *
     * @return array
     */
    public static function parseFieldsAnnotations($className, $onlyAnnotation = true)
    {
        $reflect = new \ReflectionClass($className);
        $fields = array();
        $methods = array();

//        $method = new \ReflectionMethod($methods, $name);

        /**
         * @var $method \ReflectionMethod
         */
        foreach ($reflect->getMethods() as $method) {
            $methods[$method->getName()] = $method;
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

            // exclude not annotated fields
            if (count($annotations)) {
                foreach ($methods as $methodName => $method) {
                    switch ($methodName) {
                        case self::GET . $methodHash: $annotations[self::GETTER] = $methodName;
                            break;
                        case self::SET . $methodHash: $annotations[self::SETTER] = $methodName;
                            break;
                        case self::HAS . $methodHash: $annotations[self::HAS_METHOD] = $methodName;
                            break;
                        case self::IS . $methodHash: $annotations[self::IS_METHOD] = $methodName;
                            break;
                    }
                }

                // try to find getter if not founded before 
                if (!isset($annotations[self::GETTER])) {
                    if (isset($annotations[self::HAS_METHOD])) {
                        $annotations[self::GETTER] = $annotations[self::HAS_METHOD];
                    } elseif (isset($annotations[self::IS_METHOD])) {
                        $annotations[self::GETTER] = $annotations[self::IS_METHOD];
                    }
                }

                if (isset($annotations[self::COLUMN]) && isset($annotations[self::COLUMN][self::NAME])) {
                    $annotations[self::NAME] = strtolower($annotations[self::COLUMN][self::NAME]);
                } elseif (isset($annotations[self::JOIN_COLUMN]) && isset($annotations[self::JOIN_COLUMN][self::NAME])) {
                    $annotations[self::NAME] = strtolower($annotations[self::JOIN_COLUMN][self::NAME]);
                }
                $fields[$fieldName] = $annotations;
            } elseif (!$onlyAnnotation) {
                $fieldProperties = array();

                foreach ($methods as $methodName => $method) {
                    switch ($methodName) {
                        case self::GET . $methodHash: $fieldProperties[self::GETTER] = $methodName;
                            break;
                        case self::SET . $methodHash: $fieldProperties[self::SETTER] = $methodName;
                            break;
                        case self::HAS . $methodHash: $fieldProperties[self::HAS_METHOD] = $methodName;
                            break;
                        case self::IS . $methodHash: $fieldProperties[self::IS_METHOD] = $methodName;
                            break;
                    }
                }

                // try to find getter if not founded before 
                if (!isset($fieldProperties[self::GETTER])) {
                    if (isset($fieldProperties[self::HAS_METHOD])) {
                        $annotation[self::GETTER] = $fieldProperties[self::HAS_METHOD];
                    } elseif (isset($fieldProperties[self::IS_METHOD])) {
                        $annotation[self::GETTER] = $fieldProperties[self::IS_METHOD];
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
