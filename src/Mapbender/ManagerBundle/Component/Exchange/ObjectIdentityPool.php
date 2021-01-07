<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


use Doctrine\Common\Util\ClassUtils;
use Mapbender\CoreBundle\Utils\ArrayUtil;

class ObjectIdentityPool
{
    /** @var mixed[] */
    protected $entries;
    /** @var string[] */
    protected $uniqueClassNames;

    public function __construct()
    {
        $this->entries = array();
        $this->uniqueClassNames = array();
    }

    /**
     * @param string $className
     * @param string[] $identifier
     * @return mixed|null
     */
    public function get($className, $identifier)
    {
        $key = $this->getTrackingKey($className, $identifier);
        return ArrayUtil::getDefault($this->entries, $key, null);
    }

    /**
     * @param string $className
     * @param array $identifier
     * @param mixed $value
     * @param bool $allowReplace
     * @return bool
     */
    public function addEntry($className, $identifier, $value, $allowReplace = false)
    {
        $key = $this->getTrackingKey($className, $identifier);
        $isNew = !array_key_exists($key, $this->entries);
        if ($allowReplace || $isNew) {
            $this->entries[$key] = $value;
            $this->uniqueClassNames[$className] = $className;
        }
        return $isNew;
    }

    /**
     * @param static $other
     * @param bool $allowReplace
     */
    public function merge($other, $allowReplace = false)
    {
        if ($allowReplace) {
            $this->entries = array_replace($other->entries, $this->entries);
        } else {
            $this->entries = array_replace($this->entries, $other->entries);
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
        return ClassUtils::getRealClass($className) . '#' . serialize($identifier);
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
