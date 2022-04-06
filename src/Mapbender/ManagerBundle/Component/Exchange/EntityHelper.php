<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;

class EntityHelper extends AbstractObjectHelper
{
    /** @var ClassMetadata */
    protected $classMeta;

    /** @var static[] */
    protected static $instances = array();

    /**
     * @param ClassMetadata $classMeta
     * @param string $className
     */
    public function __construct(ClassMetadata $classMeta, $className)
    {
        $this->classMeta = $classMeta;
        parent::__construct($className);
    }

    /**
     * @param EntityManagerInterface $em
     * @param string|object $objectOrClassName
     * @return static|null
     * @throws \ReflectionException
     */
    public static function getInstance(EntityManagerInterface $em, $objectOrClassName)
    {
        $className = is_string($objectOrClassName) ? $objectOrClassName : get_class($objectOrClassName);
        if (!array_key_exists($className, static::$instances)) {
            static::$instances[$className] = static::factory($em, $objectOrClassName) ?: false;
        }
        $instance = static::$instances[$className] ?: null;
        if ($instance && (!$instance instanceof self)) {
            return null;
        }
        return $instance;
    }

    /**
     * @param EntityManagerInterface $em
     * @param string|object $objectOrClassName
     * @return static|null
     * @throws \ReflectionException
     */
    protected static function factory(EntityManagerInterface $em, $objectOrClassName)
    {
        $className = is_string($objectOrClassName) ? $objectOrClassName : get_class($objectOrClassName);
        /** @var \Doctrine\ORM\Mapping\ClassMetaDataFactory $factory */
        $factory = $em->getMetadataFactory();
        try {
            $classMeta = $factory->getMetadataFor($className);
            return new static($classMeta, $className);
        } catch (MappingException $e) {
            return null;
        }
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMeta()
    {
        return $this->classMeta;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->classMeta->getName();
    }

    /**
     * @param object $entity
     * @return string[]
     */
    public function extractIdentifier($entity)
    {
        return $this->extractProperties($entity, $this->classMeta->getIdentifier());
    }
}
