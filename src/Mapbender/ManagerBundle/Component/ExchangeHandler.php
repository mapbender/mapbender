<?php

namespace Mapbender\ManagerBundle\Component;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
abstract class ExchangeHandler
{
    const KEY_CLASS         = '__class__';

    /** @var EntityManagerInterface $em */
    protected $em;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }
}
