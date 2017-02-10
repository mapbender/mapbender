<?php
namespace Mapbender\CoreBundle\Component;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Symfony\Component\DependencyInjection\ContainerInterface;

//use 

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
     * @var mixed|SourceInstance entity
     */
    protected $entity;

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

    public static function find(ContainerInterface $container, $entityClass, $entityId)
    {
        return $container->get('doctrine')->getRepository($entityClass)->find($entityId);
    }

    /**
     * @param ContainerInterface $container
     * @param  SourceInstance $entity
     * @return SourceInstanceEntityHandler|null
     */
    public static function createHandler(ContainerInterface $container, $entity)
    {
        $bundles            = $container->get('kernel')->getBundles();
        $reflect            = new \ReflectionClass($entity);
        $entityClass        = ClassUtils::getClass($entity);
        $entityBundleFolder = substr($entityClass, 0, strpos($entityClass, '\\Entity\\'));
        $entityName         = $reflect->getShortName();
        foreach ($bundles as $type => $bundle) {
            if (strpos( get_class($bundle), $entityBundleFolder) === 0) {
                $handlerClass = $entityBundleFolder . '\\Component\\' . $entityName . 'EntityHandler';
                if (class_exists($handlerClass)) {
                    return new $handlerClass($container, $entity);
                } else {
                    return null;
                }
            }
        }
        return null;
    }

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

    public static function findAll(
        ContainerInterface $container,
        $entityClass,
        $criteria = array(),
        $accessControl = null
    ) {
        $em     = $container->get('doctrine')->getManager();
        $result = $em->getRepository($entityClass)->findAll($criteria);
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
