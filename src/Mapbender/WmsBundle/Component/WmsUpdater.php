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

    public function findLayer($layer, $layerList)
    {
        $founded = null;
        $num     = 0;
        foreach ($layerList as $layerTmp) {
            if ($layer->getName() === $layerTmp->getName()) {
                $founded = $layerTmp;
                $num++;
            }
        }
        if ($num > 1) {
            # not found or $layerOrig name is null and more as one layer from $layerList with name is null
            throw new NotUpdateableException("WMS Layer: ".$layerOrigSublayer->getName()
            ."(".$layerOrigSublayer->getName().") can't be updated.");
        }
        return $founded;
    }

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