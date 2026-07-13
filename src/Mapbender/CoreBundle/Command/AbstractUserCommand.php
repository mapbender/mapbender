<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use FOM\UserBundle\Entity\User;
use Symfony\Component\Console\Command\Command;

abstract class AbstractUserCommand extends Command
{
    public function __construct(protected ManagerRegistry $managerRegistry)
    {
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
        $repository = $this->managerRegistry->getRepository(User::class);
        return $repository;
    }
}
