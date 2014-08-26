<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Entity\PrintQueue;

/**
 * Interface PriorityVoterInterface
 *
 *  Used to prioritize print queue
 *
 * @see
 * @package   Mapbender\PrintBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
interface PriorityVoterInterface
{
    /**
     * Manage queue priority by given entity
     *
     * @param array $payload
     * @return bool
     */
    public function getPriority(array $payload);
}