<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Utils;

use Doctrine\ORM\EntityManager;

/**
 * Description of EntityUtils
 *
 * @author Paul Schmidt
 */
class EntityUtil
{
    /**
     * Returns an unique value for an unique field.
     * 
     * @param \Doctrine\ORM\EntityManager $em an entity manager
     * @param string $entityName entity name
     * @param string $uniqueField name of the unique field
     * @param string $toUniqueValue value to the unique field
     * @param string $suffix suffix to generate an unique value
     * @return string an unique value
     */
    public static function getUniqueValue(EntityManager $em, $entityName, $uniqueField, $toUniqueValue, $suffix = "")
    {
        $criteria = array();
        $criteria[$uniqueField] = $toUniqueValue;
        $obj = $em->getRepository($entityName)->findOneBy($criteria);
        if($obj === null){
            return $toUniqueValue;
        } else {
            $count = 0;
            do {
                $newUniqueValue = $toUniqueValue . $suffix . ($count > 0 ? $count : '');
                $count++;
                $criteria[$uniqueField] = $newUniqueValue;
            } while ($em->getRepository($entityName)->findOneBy($criteria));
            return $newUniqueValue;
        }
    }
    
    public static function findOneBy(EntityManager $em, $entityName, $field, $fieldValue)
    {
        $criteria = array();
        $criteria[$field] = $fieldValue;
        $obj = $em->getRepository($entityName)->findOneBy($criteria);
        return $obj;
    }
}
