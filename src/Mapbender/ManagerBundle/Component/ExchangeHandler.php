<?php

namespace Mapbender\ManagerBundle\Component;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
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

    protected $legacyClassMapping = array(
        'Mapbender\WmtsBundle\Entity\TileMatrix' => 'Mapbender\WmtsBundle\Component\TileMatrix',
        'Mapbender\WmtsBundle\Entity\TileMatrixSetLink' => 'Mapbender\WmtsBundle\Component\TileMatrixSetLink',
        'Mapbender\WmtsBundle\Entity\UrlTemplateType' => 'Mapbender\WmtsBundle\Component\UrlTemplateType',
        'Mapbender\WmtsBundle\Entity\Style' => 'Mapbender\WmtsBundle\Component\Style',
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
                return true;
            }
        }
        return false;
    }
}
