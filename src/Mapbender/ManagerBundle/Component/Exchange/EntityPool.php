<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * Maps imported entities to their "import space" identifiers, which are
 * usually old ids from the export origin that need adjusting.
 */
class EntityPool
{
    /** @var object[] */
    protected $entities;
    /** @var string[] */
    protected $uniqueClassNames;

    public function __construct()
    {
        $this->entities = array();
        $this->uniqueClassNames = array();
    }

    /**
     * @param string $className
     * @param string[] $identifier
     * @return object|null
     */
    public function get($className, $identifier)
    {
        $key = $this->getTrackingKey(ClassUtils::getRealClass($className), $identifier);
        return ArrayUtil::getDefault($this->entities, $key, null);
    }

    /**
     * @return string[]
     */
    public function getUniqueClassNames()
    {
        return array_values($this->uniqueClassNames);
    }

    /**
     * @param object $entity
     * @param string[] $identifier
     * @param bool $allowReplace
     */
    public function add($entity, $identifier, $allowReplace = false)
    {
        $className = ClassUtils::getClass($entity);
        $key = $this->getTrackingKey(ClassUtils::getClass($entity), $identifier);
        if ($allowReplace || empty($this->entities[$key])) {
            $this->entities[$key] = $entity;
            $this->uniqueClassNames[$className] = $className;
        }
    }

    /**
     * @param string $className
     * @param string[] $identifier
     * @return string
     */
    protected function getTrackingKey($className, $identifier)
    {
        $identifier = $this->normalizeIdentifier($identifier);
        return $className . '#' . serialize($identifier);
    }

    protected function normalizeIdentifier($identifierIn)
    {
        $identifierOut = array();
        foreach ($identifierIn as $k => $v) {
            if (is_int($v) || is_float($v)) {
                $identifierOut[$k] = strval($v);
            } else {
                $identifierOut[$k] = $v;
            }
        }
        ksort($identifierOut);
        return $identifierOut;
    }
}
