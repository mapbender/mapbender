<?php
namespace Mapbender\ManagerBundle\Component;

/**
 *
 * @author Paul Schmidt
 */
interface Mapper
{
    /**
     * Returns an id of a given class name from a mapper.
     * @param string $className
     * @param integeer $id id
     * @param boolean $isSuperClass flag if class name is a super class.
     */
    public function getIdentFromMapper($className, $id, $isSuperClass = false);

    /**
     * Checks if given class or it parent is a class to find.
     * @param type $classIs
     * @param type $classToFind
     * @return boolean true if found, otherwise false
     */
    public function findSuperClass($classIs, $classToFind);
}
