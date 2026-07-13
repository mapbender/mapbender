<?php

namespace Mapbender\ManagerBundle\Component;

use Mapbender\CoreBundle\Entity\Keyword;
use Mapbender\WmtsBundle\Component\TileMatrix;
use Mapbender\WmtsBundle\Component\TileMatrixSetLink;
use Mapbender\WmtsBundle\Component\UrlTemplateType;
use Mapbender\WmtsBundle\Component\Style;
use Mapbender\CoreBundle\Utils\DoctrineClassUtil;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @author Paul Schmidt
 */
abstract class ExchangeHandler
{
    const KEY_CLASS         = '__class__';

    protected $entityClassBlacklist = [
        Keyword::class,
    ];

    protected static $legacyClassMapping = [
        'Mapbender\WmtsBundle\Entity\TileMatrix' => TileMatrix::class,
        'Mapbender\WmtsBundle\Entity\TileMatrixSetLink' => TileMatrixSetLink::class,
        'Mapbender\WmtsBundle\Entity\UrlTemplateType' => UrlTemplateType::class,
        'Mapbender\WmtsBundle\Entity\Style' => Style::class,
    ];

    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(protected EntityManagerInterface $em)
    {
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
        $className = DoctrineClassUtil::getRealClass($className);
        foreach ($list as $listName) {
            if (is_a($className, $listName, true)) {
                return true;
            }
        }
        return false;
    }
}
