<?php

namespace Mapbender\PrintBundle\Component;

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