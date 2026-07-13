<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


use Doctrine\ORM\Mapping\ClassMetaDataFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use Mapbender\CoreBundle\Utils\DoctrineClassUtil;

class EntityHelper extends AbstractObjectHelper
{
    /** @var static[] */
    protected static $instances = [];

    /**
     * @param ClassMetadata $classMeta
     * @param string $className
     */
    public function __construct(protected ClassMetadata $classMeta, $className)
    {
        parent::__construct(DoctrineClassUtil::getRealClass($className));
    }

    /**
     * @param EntityManagerInterface $em
     * @param string|object $objectOrClassName
     * @return static|null
     * @throws \ReflectionException
     */
    public static function getInstance(EntityManagerInterface $em, $objectOrClassName): ?EntityHelper
    {
        $className = is_string($objectOrClassName) ? $objectOrClassName : $objectOrClassName::class;
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
    protected static function factory(EntityManagerInterface $em, $objectOrClassName): ?self
    {
        $className = is_string($objectOrClassName) ? $objectOrClassName : $objectOrClassName::class;
        /** @var ClassMetaDataFactory $factory */
        $factory = $em->getMetadataFactory();
        try {
            $classMeta = $factory->getMetadataFor($className);
            return new static($classMeta, $className);
        } catch (MappingException) {
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
    public function getClassName(): string
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
