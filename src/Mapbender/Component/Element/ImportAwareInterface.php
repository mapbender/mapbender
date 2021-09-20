<?php


namespace Mapbender\Component\Element;

use Mapbender\CoreBundle\Entity\Element;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * Interface for service-type elements that reference other entities by id from their configuration,
 * and must update them during application import
 */
interface ImportAwareInterface
{
    public function onImport(Element $element, Mapper $mapper);
}
