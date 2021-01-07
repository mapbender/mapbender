<?php
namespace Mapbender\CoreBundle\Component;

use Doctrine\ORM\EntityManagerInterface;
use Mapbender\CoreBundle\Entity\Keyword;

/**
 * Class to update of Keywords
 *
 * @author Paul Schmidt
 */
class KeywordUpdater
{

    /**
     * @param Keyword $keyword
     * @param array $keywordList
     * @return bool
     */
    public static function keywordExists($keyword, $keywordList)
    {
        foreach ($keywordList as $keywordTemp) {
            if ($keyword->getValue() == $keywordTemp->getValue()) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update keywords
     *
     * @param ContainingKeyword $componentOld
     * @param ContainingKeyword $compenentNew
     * @param EntityManagerInterface $entityManager
     * @param string $newKeywordClass
     */
    public static function updateKeywords(
        ContainingKeyword $componentOld,
        ContainingKeyword $compenentNew,
        $entityManager,
        $newKeywordClass
    ) {
        foreach ($componentOld->getKeywords() as $keyword) {
            if (!self::keywordExists($keyword, $compenentNew->getKeywords())) {
                $componentOld->getKeywords()->removeElement($keyword);
                $entityManager->remove($keyword);
            }
        }
        foreach ($compenentNew->getKeywords() as $keyword) {
            if (!self::keywordExists($keyword, $componentOld->getKeywords())) {
                /** @var Keyword $keywordNew */
                $keywordNew = new $newKeywordClass();
                $keywordNew->setValue($keyword->getValue());
                $keywordNew->setReferenceObject($componentOld);
                $componentOld->addKeyword($keywordNew);

                $entityManager->remove($keyword);
            }
        }
    }
}
