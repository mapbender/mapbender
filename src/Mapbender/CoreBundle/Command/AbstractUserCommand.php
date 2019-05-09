<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Component\UserHelperService;
use FOM\UserBundle\Security\UserHelper;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractUserCommand extends ContainerAwareCommand
{
    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var RegistryInterface $doctrine */
        $doctrine = $this->getContainer()->get('doctrine');
        /** @var EntityManagerInterface $manager */
        $manager = $doctrine->getManager();
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

    /**
     * @return EntityRepository
     */
    protected function getGroupRepository()
    {
        /** @var EntityRepository $repository */
        $repository = $this->getEntityManager()->getRepository('FOMUserBundle:Group');
        return $repository;
    }

    /**
     * @return UserHelperService|UserHelper
     */
    protected function getUserHelper()
    {
        /** @var UserHelperService|null $helperService */
        $helperService = $this->getContainer()->get('fom.user_helper.service', ContainerInterface::NULL_ON_INVALID_REFERENCE);
        if ($helperService) {
            return $helperService;
        } else {
            // Old FOM < v3.1.1
            return new UserHelper($this->getContainer());
        }
    }
}
