<?php


namespace Mapbender\ManagerBundle\Component\Exchange;


use Doctrine\ORM\EntityManagerInterface;

class ImportState
{
    /** @var EntityPool */
    protected $entityPool;
    /** @var ObjectIdentityPool */
    protected $globalList;

    /**
     * @param EntityManagerInterface $em
     * @param array $data
     * @param EntityPool|null $entityPool
     */
    public function __construct(EntityManagerInterface $em, array $data, EntityPool $entityPool = null)
    {
        $this->globalList = new ObjectIdentityPool();
        foreach ($data as $className => $instanceList) {
            $eh = EntityHelper::getInstance($em, $className);
            if (!$eh) {
                if (class_exists($className)) {
                    throw new \LogicException("No entity helper for importable class {$className}");
                }
                continue;
            }
            $identifierNames = $eh->getClassMeta()->getIdentifier();

            foreach ($instanceList as $instanceData) {
                $identValues = array_intersect_key($instanceData, array_flip($identifierNames));
                $this->globalList->addEntry($className, $identValues, $instanceData, false);
            }
        }
        $this->entityPool = $entityPool ?: new EntityPool();
    }

    /**
     * @return EntityPool
     */
    public function getEntityPool()
    {
        return $this->entityPool;
    }

    /**
     * @param string $className
     * @param string[] $identifier
     * @return array|null
     */
    public function getEntityData($className, $identifier)
    {
        return $this->globalList->get($className, $identifier);
    }
}
