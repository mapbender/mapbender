<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\User;
use Mapbender\CoreBundle\Component\ApplicationYAMLMapper;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Component\Console\Command\Command;

abstract class AbstractApplicationTransportCommand extends Command
{
    /** @var EntityManagerInterface */
    protected $defaultEntityManager;
    /** @var ImportHandler */
    protected $importHandler;
    /** @var ApplicationYAMLMapper */
    protected $yamlRepository;

    public function __construct(EntityManagerInterface $defaultEntityManager,
                                ImportHandler $importHandler,
                                ApplicationYAMLMapper $yamlRepository)
    {
        parent::__construct(null);
        $this->defaultEntityManager = $defaultEntityManager;
        $this->importHandler = $importHandler;
        $this->yamlRepository = $yamlRepository;
    }

    /**
     * @return EntityRepository
     */
    protected function getApplicationRepository()
    {
        return $this->getEntityRepository('MapbenderCoreBundle:Application');
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getDefaultEntityManager()
    {
        return $this->defaultEntityManager;
    }

    /**
     * @return ImportHandler
     */
    protected function getApplicationImporter()
    {
        return $this->importHandler;
    }

    /**
     * @param string $className
     * @return EntityRepository
     */
    protected function getEntityRepository($className)
    {
        /** @var EntityRepository $repository */
        $repository = $this->getDefaultEntityManager()->getRepository($className);
        return $repository;
    }

    /**
     * @return User|null
     */
    protected function getRootUser()
    {
        foreach ($this->getEntityRepository('FOMUserBundle:User')->findAll() as $user) {
            /** @var User $user*/
            if ($user->isAdmin()) {
                return $user;
            }
        }
        return null;
    }
}
