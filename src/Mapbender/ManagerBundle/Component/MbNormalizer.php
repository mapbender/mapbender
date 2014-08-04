<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Mapbender\ManagerBundle\Component;

use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
/**
 * Description of MbNormalizer
 *
 * @author Paul Schmidt
 */
class MbNormalizer implements NormalizerInterface
{
    public function normalize($object, $format = null)
    {
        $data = array();

        $reflectionClass = new \ReflectionClass($object);

        $data['__jsonclass__'] = array(
            get_class($object),
            array(), // constructor arguments
        );

        foreach ($reflectionClass->getMethods(\ReflectionMethod::IS_PUBLIC) as $reflectionMethod) {
            if (strtolower(substr($reflectionMethod->getName(), 0, 3)) !== 'get') {
                continue;
            }

            if ($reflectionMethod->getNumberOfRequiredParameters() > 0) {
                continue;
            }

            $property = lcfirst(substr($reflectionMethod->getName(), 3));
            $value = $reflectionMethod->invoke($object);

            $data[$property] = $value;
        }

        return $data;
    }
    
    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null)
    {
        return is_object($data) && $this->supports(get_class($data));
    }
    
    /**
     * Checks if the given class has any get{Property} method.
     *
     * @param string $class
     *
     * @return bool
     */
    private function supports($class)
    {
        $class = new \ReflectionClass($class);
        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($this->isGetMethod($method)) {
                return true;
            }
        }

        return false;
    }
}