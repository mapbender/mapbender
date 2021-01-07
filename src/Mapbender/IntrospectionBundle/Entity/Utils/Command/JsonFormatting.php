<?php


namespace Mapbender\IntrospectionBundle\Entity\Utils\Command;

/**
 * Pre-baked formatting clase generating typical JavaScript-style lists / Arrays of objects where ids
 * are embedded into the objects.
 *
 */
class JsonFormatting extends DataItemFormatting
{
    public function __construct($nameKey)
    {
        parent::__construct($nameKey, false, true);
    }
}

/*
    {
       "id" : "159",
       "title" : "Strecke Hektometer",
       "instances" : [
          {
             "title" : "Strecke Hektometer",
             "enabled" : false,
             "id" : "709"
          }
       ]
    },
    {
       "id" : "167",
       "title" : "flimasDaten f\u00fcr DB Energie",
       "instances" : [
          {
             "title" : "flimasDaten f\u00fcr DB Energie",
             "enabled" : false,
             "id" : "708"
          }
       ]
    },
*/
