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
    public static function parseFields($className, $onlyAnnotation = true)
    {
        $fields = EntityAnnotationParser::parseFieldsAnnotations($className, $onlyAnnotation);
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
            $propProps = array();
            $fieldName = $property->getName();
            foreach ($methods as $methodName => $method) {
                $methodHash = ucwords($fieldName);
                switch ($methodName) {
                    case 'get' . $methodHash: $propProps['getter'] = $methodName;
                        break;
                    case 'set' . $methodHash: $propProps['setter'] = $methodName;
                        break;
                    case 'has' . $methodHash: $propProps['hasMethod'] = $methodName;
                        break;
                    case 'is' . $methodHash: $propProps['isMethod'] = $methodName;
                        break;
                }
            }

            // try to find getter if not founded before 
            if (!isset($propProps['getter'])) {
                if (isset($propProps['hasMethod'])) {
                    $annotation['getter'] = $propProps['hasMethod'];
                } elseif (isset($propProps['isMethod'])) {
                    $annotation['getter'] = $propProps['isMethod'];
                }
            }
            $fields[$fieldName] = $propProps;
        }
        return $fields;
    }
}
