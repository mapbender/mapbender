<?php


namespace Mapbender\CoreBundle\Command;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use FOM\UserBundle\Entity\User;
use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Mapbender;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\ManagerBundle\Component\ImportHandler;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

abstract class AbstractApplicationTransportCommand extends ContainerAwareCommand
{
    /**
     * @return EntityRepository
     */
    protected function getApplicationRepository()
    {
        return $this->getEntityRepository('MapbenderCoreBundle:Application');
    }

    /**
     * @param string $slug
     * @return Application|null
     */
    protected function getYamlApplication($slug)
    {
        /** @var Mapbender $m */
        $m = $this->getContainer()->get('mapbender');
        $apps = $m->getYamlApplicationEntities(false);
        return ArrayUtil::getDefault($apps, $slug, null);
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getDefaultEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine.orm.default_entity_manager');
        return $em;
    }

    /**
     * @return ImportHandler
     */
    protected function getApplicationImporter()
    {
        /** @var ImportHandler $service */
        $service = $this->getContainer()->get('mapbender.application_importer.service');
        return $service;
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
