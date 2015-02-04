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
                    case EntityAnnotationParser::GET . $methodHash: $annotations[EntityAnnotationParser::GETTER] = $methodName;
                        break;
                    case EntityAnnotationParser::SET . $methodHash: $annotations[EntityAnnotationParser::SETTER] = $methodName;
                        break;
                    case EntityAnnotationParser::HAS . $methodHash: $annotations[EntityAnnotationParser::HAS_METHOD] = $methodName;
                        break;
                    case EntityAnnotationParser::IS . $methodHash: $annotations[EntityAnnotationParser::IS_METHOD] = $methodName;
                        break;
                }
            }

            // try to find getter if not founded before 
                if (!isset($annotations[EntityAnnotationParser::GETTER])) {
                    if (isset($annotations[EntityAnnotationParser::HAS_METHOD])) {
                        $annotations[EntityAnnotationParser::GETTER] = $annotations[EntityAnnotationParser::HAS_METHOD];
                    } elseif (isset($annotations[EntityAnnotationParser::IS_METHOD])) {
                        $annotations[EntityAnnotationParser::GETTER] = $annotations[EntityAnnotationParser::IS_METHOD];
                    }
                }
            
            
            $fields[$fieldName] = $propProps;
        }
        return $fields;
    }

}
