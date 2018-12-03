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
     * @param int $id id
     * @param boolean $isSuperClass flag if class name is a super class.
     */
    public function getIdentFromMapper($className, $id, $isSuperClass = false);
}
