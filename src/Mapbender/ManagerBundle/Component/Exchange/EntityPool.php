<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


use Doctrine\Common\Util\ClassUtils;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * Maps imported entities to their "import space" identifiers, which are
 * usually old ids from the export origin that need adjusting.
 *
 * @method object|null get(string $className, string[] $identifier)
 */
class EntityPool extends ObjectIdentityPool implements Mapper
{
    /**
     * @param object $entity
     * @param string[] $identifier
     * @param bool $allowReplace
     * @return boolean
     */
    public function add($entity, $identifier, $allowReplace = false)
    {
        $className = ClassUtils::getClass($entity);
        return $this->addEntry($className, $identifier, $entity, $allowReplace);
    }

    /**
     *
     * @inheritdoc
     */
    public function getIdentFromMapper($className, $id, $isSuperClass = false): null|int|string
    {
        $entity = $this->getMappedEntity($className, $id, $isSuperClass);
        if ($entity && method_exists($entity, 'getId')) {
            return $entity->getId();
        }
        return null;

    }
    public function getMappedEntity($className, $id, $isSuperClass = false): ?object
    {
        $identValues = array(
            'id' => $id,
        );
        $entity = $this->get($className, $identValues);
        if (!$entity && $isSuperClass) {
            $realBaseClass = ClassUtils::getRealClass($className);
            foreach ($this->uniqueClassNames as $uniqueClass) {
                if (class_exists($uniqueClass) && is_a($uniqueClass, $realBaseClass, true)) {
                    if ($entity = $this->get($uniqueClass, $identValues)) {
                        break;
                    }
                }
            }
        }
        return $entity;
    }
}
