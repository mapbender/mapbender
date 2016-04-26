<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\CoreBundle\Component;

/**
 * Class to update of Keywords
 *
 * @author Paul Schmidt
 */
class KeywordUpdater
{

    public static function keywordExists($keyword, $keywordList)
    {
        foreach ($keywordList as $keywordTemp) {
            if ($keyword->getValue() == $keywordTemp->getValue()) {
                return true;
            }
        }
        return false;
    }

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
//        $entityManager->refresh($componentOld);
        foreach ($compenentNew->getKeywords() as $keyword) {
            if (!self::keywordExists($keyword, $componentOld->getKeywords())) {
                $keywordNew = new $newKeywordClass();
                $keywordNew->setValue($keyword->getValue());
//                $entityManager->persist($keywordNew);
                $keywordNew->setReferenceObject($componentOld);
//                $entityManager->persist($keywordNew);
                $componentOld->addKeyword($keywordNew);
//                $entityManager->persist($keywordNew->getReferenceObject());
            }
        }
    }
}
