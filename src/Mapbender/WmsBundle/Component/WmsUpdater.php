<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Mapbender\WmsBundle\Component;

use Mapbender\CoreBundle\Component\ContainingKeyword;
use Mapbender\CoreBundle\Component\ReflectionHandler;

/**
 * Description of WmsUpdater
 *
 * @author Paul Schmidt
 */
class WmsUpdater extends ReflectionHandler
{

    public function keywordExists($keyword, $keywordList)
    {
        foreach ($keywordList as $keywordTemp) {
            if ($keyword->getValue() === $keywordTemp->getValue()) {
                return true;
            }
        }
        return false;
    }

    public function updateKeywords(ContainingKeyword $sourceOld, ContainingKeyword $sourceNew, $entityManager,
                                   $newKeywordClass)
    {
        foreach ($sourceOld->getKeywords() as $keyword) {
            if (!$this->keywordExists($keyword, $sourceNew->getKeywords())) {
                $entityManager->remove($keyword);
                $entityManager->flush();
            }
        }
        $entityManager->merge($sourceOld);
        foreach ($sourceNew->getKeywords() as $keyword) {
            if (!$this->keywordExists($keyword, $sourceOld->getKeywords())) {
                $keywordNew = new $newKeywordClass();
                $keywordNew->setValue($keyword->getValue());
                $keywordNew->setReferenceObject($sourceOld);
                $sourceOld->addKeyword($keywordNew);
            }
        }
    }
}