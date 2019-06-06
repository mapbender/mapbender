<?php

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Description of ExportHandler
 *
 * @author Paul Schmidt
 */
abstract class ExchangeHandler
{
    const KEY_CLASS         = '__class__';

    /** @var EntityManagerInterface $em */
    protected $em;

    protected $entityClassBlacklist = array(
        'Mapbender\CoreBundle\Entity\Keyword',
    );

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @param string $className
     * @return bool
     */
    protected function isEntityClassBlacklisted($className)
    {
        return $this->checkClassList($className, $this->entityClassBlacklist);
    }

    /**
     * @param string $className
     * @param string[] $list
     * @return bool
     */
    protected function checkClassList($className, $list)
    {
        $className = ClassUtils::getRealClass($className);
        foreach ($list as $listName) {
            if (is_a($className, $listName, true)) {
                // die("Yes yes yes! " . $className . " " . $listName . "\n");
                return true;
            }
        }
        return false;
    }
}
