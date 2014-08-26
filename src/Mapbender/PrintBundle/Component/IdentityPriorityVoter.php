<?php
/**
 *
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 25.08.2014 by WhereGroup GmbH & Co. KG
 */

namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Entity\PrintQueue;

/**
 * Class IdentityPriorityVoter
 *
 * Used to prioritize print queue
 *
 * @package   Mapbender\PrintBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class IdentityPriorityVoter implements PriorityVoterInterface
{
    /**
     * @inheritdoc
     */
    public function getPriority(array $payload)
    {
        return true;
    }
}