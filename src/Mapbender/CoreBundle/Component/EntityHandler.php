<?php
namespace Mapbender\CoreBundle\Component;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Description of EntityHandler
 *
 * @author Paul Schmidt
 */
class EntityHandler
{
    /**
     * @var ContainerInterface container
     */
    protected $container;

    /**
     * @var SourceInstanceItem entity
     */
    protected $entity;

    /**
     * EntityHandler constructor.
     *
     * @param ContainerInterface $container
     * @param                    $entity
     */
    public function __construct(ContainerInterface $container, $entity)
    {
        $this->container = $container;
        $this->entity    = $entity;
    }

    /**
     * Persists the entity
     */
    public function save()
    {
        $this->getEntityManager()->persist($this->entity);
    }

    /**
     * Removes the entity from a database
     */
    public function remove()
    {
        $this->getEntityManager()->remove($this->entity);
    }

    /**
     * @param ContainerInterface $container
     * @param  Source|SourceInstance|object $entity
     * @return static|null
     * @todo: never return null
     */
    public static function createHandler(ContainerInterface $container, $entity)
    {
        $entityClass        = ClassUtils::getClass($entity);
        $handlerClass = str_replace('\\Entity\\', '\\Component\\', $entityClass) . 'EntityHandler';

        if (class_exists($handlerClass)) {
            return new $handlerClass($container, $entity);
        } else {
            return null;
        }
    }

    /**
     * @return EntityManagerInterface
     */
    protected function getEntityManager()
    {
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManager();
        return $em;
    }
}
