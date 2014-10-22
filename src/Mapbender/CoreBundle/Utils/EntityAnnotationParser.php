<?php

/*
 */

namespace Mapbender\CoreBundle\Utils;

/**
 * Description of EntityAnnotationParser
 *
 * @author Andriy Oblivantsev
 * @author Paul Schmidt
 */
class EntityAnnotationParser
{

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
                if (preg_match('/^ORM\\\(.+)/s', $annotation, $matches) || preg_match('/^Assert\\\(.+)/s', $annotation, $matches)) {
                    $matches = preg_split('/\(/', $matches[1]);
                    $key = $matches[0];
                    // if matched annotation has some values, parse and add to value array
                    $annotations[$key] = isset($matches[1]) ? self::getAnnotationGroupedValues(
                            preg_replace('/\)$/', '', $matches[1])
                        ) : true;
                }
            }
            
            $fieldName  = $property->getName();
            $methodHash = "";
            foreach(preg_split("/_/", $fieldName) as $chunk){
                $chunk = ucwords($chunk);
                $methodHash .= $chunk;
            }

            // exclude not annotated fields
            if (count($annotations)) {
                foreach ($methods as $methodName => $method) {
                    switch ($methodName) {
                        case 'get' . $methodHash: $annotations['getter'] = $methodName;
                            break;
                        case 'set' . $methodHash: $annotations['setter'] = $methodName;
                            break;
                        case 'has' . $methodHash: $annotations['hasMethod'] = $methodName;
                            break;
                        case 'is' . $methodHash: $annotations['isMethod'] = $methodName;
                            break;
                    }
                }

                // try to find getter if not founded before 
                if (!isset($annotations['getter'])) {
                    if (isset($annotations['hasMethod'])) {
                        $annotations['getter'] = $annotations['hasMethod'];
                    } elseif (isset($annotations['isMethod'])) {
                        $annotations['getter'] = $annotations['isMethod'];
                    }
                }

                if (isset($annotations["Column"]) && isset($annotations["Column"]["name"])) {
                    $annotations['name'] = strtolower($annotations["Column"]["name"]);
                } elseif (isset($annotations["JoinColumn"]) && isset($annotations["JoinColumn"]["name"])) {
                    $annotations['name'] = strtolower($annotations["JoinColumn"]["name"]);
                }
                $fields[$fieldName] = $annotations;
            } elseif (!$onlyAnnotation) {
                $fieldProperties = array();

                foreach ($methods as $methodName => $method) {
                    switch ($methodName) {
                        case 'get' . $methodHash: $fieldProperties['getter'] = $methodName;
                            break;
                        case 'set' . $methodHash: $fieldProperties['setter'] = $methodName;
                            break;
                        case 'has' . $methodHash: $fieldProperties['hasMethod'] = $methodName;
                            break;
                        case 'is' . $methodHash: $fieldProperties['isMethod'] = $methodName;
                            break;
                    }
                }

                // try to find getter if not founded before 
                if (!isset($fieldProperties['getter'])) {
                    if (isset($fieldProperties['hasMethod'])) {
                        $annotation['getter'] = $fieldProperties['hasMethod'];
                    } elseif (isset($fieldProperties['isMethod'])) {
                        $annotation['getter'] = $fieldProperties['isMethod'];
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
