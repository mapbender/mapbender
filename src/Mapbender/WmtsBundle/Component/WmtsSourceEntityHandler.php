<?php

namespace Mapbender\WmtsBundle\Component;

use Mapbender\CoreBundle\Component\SourceEntityHandler;
use Mapbender\CoreBundle\Entity\Source;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;

/**
 * Description of WmtsSourceEntityHandler
 *
 * @property WmtsSource $entity
 *
 * @author Paul Schmidt
 */
class WmtsSourceEntityHandler extends SourceEntityHandler
{
    /**
     * @inheritdoc
     */
    public function createInstance()
    {
        $source = $this->entity;
        $instance = new WmtsInstance();
        $instance->setSource($this->entity);
        $instance->setTitle($source->getTitle());
        $instance->setRoottitle($source->getTitle());

        foreach ($source->getLayers() as $layer) {
            $instLayer = $this->createInstanceLayer($layer);
            $instLayer->setSourceInstance($instance);
            $instance->addLayer($instLayer);
        }
        return $instance;
    }

    protected function createInstanceLayer(WmtsLayerSource $sourceLayer)
    {
        $instanceLayer = new WmtsInstanceLayer();
        $instanceLayer->setSourceItem($sourceLayer);
        $instanceLayer->setTitle($sourceLayer->getTitle());
        $infoFormats = array_values(array_filter($sourceLayer->getInfoformats() ?: array()));
        if ($infoFormats) {
            $instanceLayer->setInfoformat($infoFormats[0]);
            $instanceLayer->setInfo(true);
            $instanceLayer->setAllowinfo(true);
        }
        $styles = $sourceLayer->getStyles();
        if ($styles && count($styles)) {
            $instanceLayer->setStyle($styles[0]->identifier);
        }
        return $instanceLayer;
    }



    /**
     * @inheritdoc
     */
    public function update(Source $sourceNew)
    {
    }

}
