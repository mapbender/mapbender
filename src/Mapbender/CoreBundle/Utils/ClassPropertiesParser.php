<?php

/*
 */

namespace Mapbender\CoreBundle\Utils;

/**
 * Description of ClassParser
 *
 * @author Andriy Oblivantsev 
 * @author Paul Schmidt 
 */
class ClassPropertiesParser
{

    /**
     * @param string $className
     *
     * @return array
     */
    public static function parseFields($className)
    {
        $fields = EntityAnnotationParser::parseFieldsAnnotations($className);
        return count($fields) ? $fields : self::parseNonEntityFields($className);
    }

    /**
     * @param string $className
     *
     * @return array
     */
    public static function parseNonEntityFields($className)
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
            $fieldName = $property->getName();
            foreach ($methods as $methodName => $method) {
                $methodHash = ucwords($fieldName);
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
                    $annotation['getter'] = $annotations['hasMethod'];
                } elseif (isset($annotations['isMethod'])) {
                    $annotation['getter'] = $annotations['isMethod'];
                }
            }
            $fields[$fieldName] = $annotations;
        }
        return $fields;
    }
}
