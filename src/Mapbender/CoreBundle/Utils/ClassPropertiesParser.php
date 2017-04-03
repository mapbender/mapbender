<?php
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
     * @param bool   $onlyAnnotation Parse only annotation?
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
                    case EntityAnnotationParser::GET . $methodHash: $propProps[EntityAnnotationParser::GETTER] = $methodName;
                        break;
                    case EntityAnnotationParser::SET . $methodHash: $propProps[EntityAnnotationParser::SETTER] = $methodName;
                        break;
                    case EntityAnnotationParser::HAS . $methodHash: $propProps[EntityAnnotationParser::HAS_METHOD] = $methodName;
                        break;
                    case EntityAnnotationParser::IS . $methodHash: $propProps[EntityAnnotationParser::IS_METHOD] = $methodName;
                        break;
                }
            }

            // try to find getter if not founded before 
            if (!isset($propProps[EntityAnnotationParser::GETTER])) {
                if (isset($propProps[EntityAnnotationParser::HAS_METHOD])) {
                    $annotation[EntityAnnotationParser::GETTER] = $propProps[EntityAnnotationParser::HAS_METHOD];
                } elseif (isset($propProps[EntityAnnotationParser::IS_METHOD])) {
                    $annotation[EntityAnnotationParser::GETTER] = $propProps[EntityAnnotationParser::IS_METHOD];
                }
            }
            $fields[$fieldName] = $propProps;
        }
        return $fields;
    }

}
