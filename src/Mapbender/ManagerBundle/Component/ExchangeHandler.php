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
     * @param mixed $data
     * @return string|null
     */
    protected function extractClassName($data)
    {
        if (is_array($data) && array_key_exists(self::KEY_CLASS, $data)) {
            $className = $data[self::KEY_CLASS];
            if (is_array($className)) {
                $className = $className[0];
            }
            while (!empty($this->legacyClassMapping[$className])) {
                $className = $this->legacyClassMapping[$className];
            }
            return $className;
        }
        return null;
    }

    /**
     * @param array $data
     * @param string[] $fieldNames
     * @return array
     */
    protected function extractArrayFields(array $data, array $fieldNames)
    {
        return array_intersect_key($data, array_flip($fieldNames));
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
