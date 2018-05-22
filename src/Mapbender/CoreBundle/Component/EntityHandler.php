<?php
namespace Mapbender\CoreBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Component\Presenter\Application\ConfigService;
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
     * @return mixed|SourceInstance|null
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Persists the entity
     */
    public function save()
    {
        $this->container->get('doctrine')->getManager()->persist($this->entity);
    }

    /**
     * Removes the entity from a database
     */
    public function remove()
    {
        $this->container->get('doctrine')->getManager()->remove($this->entity);
    }

    /**
     * Find entity by class name and ID
     *
     * @param ContainerInterface $container
     * @param  string            $entityClass
     * @param  integer|string    $entityId
     * @return object
     */
    public static function find(ContainerInterface $container, $entityClass, $entityId)
    {
        return $container->get('doctrine')->getRepository($entityClass)->find($entityId);
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
     * @param ContainerInterface $container
     * @param                    $entity
     * @return null|\Symfony\Component\HttpKernel\Bundle\BundleInterface
     */
    public function getBundle(ContainerInterface $container, $entity)
    {
        $bundles            = $container->get('kernel')->getBundles();
        $entityBundleFolder = substr(get_class($entity), 0, strpos(get_class($entity), '\\Entity\\'));
        foreach ($bundles as $type => $bundle) {
            $bundleClass = get_class($bundle);
            if (strpos($bundleClass, $entityBundleFolder) === 0) {
                return $bundle;
            }
        }
        return null;
    }

    /**
     * Returns an unique value for an unique field.
     *
     * @param mixed $entity entity  object | entity class name
     * @param string $uniqueField name of the unique field
     * @param string $toUniqueValue value to the unique field
     * @param string $suffix suffix to generate an unique value
     * @return string an unique value
     */
    public function getUniqueValue($entity, $uniqueField, $toUniqueValue, $suffix = "")
    {
        if (is_object($entity)) {
            $entityName = get_class($entity);
        } else {
            $entityName = $entity;
        }
        $em                     = $this->container->get('doctrine')->getManager();
        $criteria               = array();
        $criteria[$uniqueField] = $toUniqueValue;
        $obj                    = $em->getRepository($entityName)->findOneBy($criteria);
        if ($obj === null) {
            return $toUniqueValue;
        } else {
            $count = 0;
            do {
                $newUniqueValue         = $toUniqueValue . $suffix . ($count > 0 ? $count : '');
                $count++;
                $criteria[$uniqueField] = $newUniqueValue;
            } while ($em->getRepository($entityName)->findOneBy($criteria));
            return $newUniqueValue;
        }
    }

    /**
     * Is entity a database entity?
     *
     * @param ContainerInterface $container
     * @param                    $entity
     * @return bool
     */
    public static function isEntity(ContainerInterface $container, $entity)
    {
        $className = is_string($entity) ? $entity : is_object($entity) ? ClassUtils::getClass($entity) : '';
        try {
            $em   = $container->get('doctrine')->getManager();
            $meta = $em->getMetadataFactory()->getMetadataFor($className);
            $is   = isset($meta->isMappedSuperclass) && $meta->isMappedSuperclass === false;
            return $is;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Fins all entities
     *
     * @param ContainerInterface $container
     * @param                    $entityClass
     * @param array              $criteria
     * @param null               $accessControl
     * @return array|ArrayCollection
     */
    public static function findAll(
        ContainerInterface $container,
        $entityClass,
        $criteria = array(),
        $accessControl = null
    ) {
        $em               = $container->get('doctrine')->getManager();
        $objectRepository = $em->getRepository($entityClass);
        $result           = $objectRepository->findAll($criteria);
        if ($accessControl) {
            $securityContext = $container->get('security.context');
            $tmp             = new ArrayCollection();
            foreach ($result as $obj) {
                if (true === $securityContext->isGranted($accessControl, $obj)) {
                    $tmp->add($obj);
                }
            }
            return $tmp;
        }
        return $result;
    }
}
