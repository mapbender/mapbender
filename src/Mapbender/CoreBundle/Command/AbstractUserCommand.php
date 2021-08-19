<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\Console\Command\Command;

abstract class AbstractUserCommand extends Command
{
    /** @var RegistryInterface */
    protected $managerRegistry;

    public function __construct(RegistryInterface $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
        parent::__construct(null);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $manager */
        $manager = $this->managerRegistry->getManager();
        return $manager;
    }

    /**
     * @return EntityRepository
     */
    protected function getRepository()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('FOMUserBundle:User');
        return $repository;
    }
}
