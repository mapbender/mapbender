<?php

namespace Mapbender\CoreBundle\Component;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerAware;
use Symfony\Component\Security\Core\SecurityContext;

/**
 * Class ServiceBase
 *
 * @package   Mapbender\PrintBundle\Component
 * @author    Andriy Oblivantsev <eslider@gmail.com>
 * @copyright 2014 by WhereGroup GmbH & Co. KG
 */
class EntitiesServiceBase extends ContainerAware
{

    /** @var string doctrine repository short name */
    protected $entityName;


    /** @var string prefix name of doctrine bundle repository */
    protected $bundleName;

    /**
     * @param string $entityName doctrine repository short name
     * @param null   $container  container
     */
    public function __construct($entityName, $container = null)
    {
        $this->setContainer($container);
        $this->bundleName = preg_replace('/[\\\]|Component[\\\].+$/s', null, get_class($this)) . ':';
        $this->entityName = $entityName;
    }

    /**
     * Get doctrine repository by entity name
     *
     * @param $name
     *
     * @return EntityRepository
     */
    public function getRepository($name = null)
    {
        $path = $this->getBundleEntityPath($name);
        return $this->getDoctrine()->getRepository($path);
    }

    /**
     * Get street entity manager
     *
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Get entity name
     *
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * Get doctrine service
     *
     * @return Registry
     */
    public function getDoctrine()
    {
        return $this->container->get('doctrine');
    }

    /**
     * Check if container is available
     *
     * @return bool
     */
    public function hasContainer()
    {
        return !is_null($this->container);
    }

    /**
     * Find entity by ID
     *
     * @param $id
     *
     * @return object
     */
    public function find($id)
    {
        return $this->getRepository($this->getEntityName())->find($id);
    }

    /**
     * Get entity query builder
     *
     * @param string     $name
     * @param string     $entityName
     * @return QueryBuilder
     */
    public function createQueryBuilder($name = 'q', $entityName = null)
    {
        return $this->getRepository($entityName)->createQueryBuilder($name);
    }

    /**
     * Delete from DB.
     *
     * @param array  $parameters     DQL Parameters
     * @param string $whereCondition Specifies one or more restrictions to the query result
     * @param string $alias          The class/type alias used in the constructed query.
     * @return mixed
     */
    public function delete(array $parameters = null, $whereCondition = 'e.id = :id', $alias='e'){
        return $this->createQueryBuilder()->setParameters($parameters)->delete($this->entityName,$alias)->where($whereCondition)->getQuery()->execute();
    }

    /**
     * Check if entity persist in DB
     *
     * @param $entity
     *
     * @return bool
     */
    public function isEntityNew($entity)
    {
        return count($this->getEntityManager()->getUnitOfWork()->getOriginalEntityData($entity)) < 1;
    }

    /**
     * Persist (save) entity in DB
     *
     * @param mixed $entity
     * @param bool  $merge merge before persist
     * @param bool  $flush flush entity after persist? (default=true)
     * @return mixed
     */
    public function persist($entity, $merge = true, $flush = true)
    {
        $entityManager = $this->getEntityManager();
        if ($merge) {
            $entity = $entityManager->merge($entity);
        }
        $entityManager->persist($entity);
        if ($flush) {
            $entityManager->flush($entity);
        }
        return $entity;
    }

    /**
     * Get native database connection
     *
     * @return Connection
     */
    public function db()
    {
        return $this->getDoctrine()->getConnection();
    }

    /**
     * @param null $name
     * @return string
     */
    private function getBundleEntityPath($name=null)
    {
        return $this->bundleName . ($name ? $name : $this->entityName);
    }

    /**
     * Get current user
     *
     * @return mixed
     */
    public function getCurrentUser()
    {
        /** @var SecurityContext $securityContext */
        $securityContext = $this->container->get('security.context');
        $token           = $securityContext->getToken();
        return $token ? $token->getUser() : null;
    }
} 