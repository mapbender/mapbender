<?php

namespace Mapbender\CoreBundle\Component;

use Symfony\Component\Serializer\Serializer;

/**
 * InOutArrayIn
 * 
 * @author Paul Schmidt
 */
interface ExchangeIn
{

    /**
     * Serialized an object to array
     * 
     * @return array a serialized object
     */
    public function toArray();
//    
//    /**
//     * Serialized an object to array
//     * 
//     * @return array a serialized object
//     */
//    public function serialize(Serializer $serializer, $format);

    /**
     * Create an object from array.
     * 
     * @param array $serialized a serialized object
     * @return Object an object from array
     */
    public static function fromArray(array $serialized);
}
