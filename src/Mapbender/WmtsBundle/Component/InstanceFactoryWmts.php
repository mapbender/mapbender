<?php


namespace Mapbender\WmtsBundle\Component;


use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceType;

class InstanceFactoryWmts extends InstanceFactoryCommon
{
    public static function createInstanceLayer(WmtsLayerSource $sourceLayer, ?WmtsInstanceLayer $parent = null)
    {
        $instanceLayer = parent::createInstanceLayer($sourceLayer);
        $infoFormats = array_values(array_filter($sourceLayer->getInfoformats() ?: array()));
        if ($infoFormats) {
            $instanceLayer->setInfoformat($infoFormats[0]);
            $instanceLayer->setInfo(true);
            $instanceLayer->setAllowinfo(true);
        }
        $styles = $sourceLayer->getStyles();
        if ($styles && count($styles)) {
            $selected = false;
            foreach ($styles as $style) {
                if ($style->getIsDefault()) {
                    $selected = $style;
                    break;
                }
            }
            $selected = $selected ?: $styles[0];
            $instanceLayer->setStyle($selected->getIdentifier());
        }
        $instanceLayer->setPriority($sourceLayer->getPriority());
        $instanceLayer->setAllowtoggle($sourceLayer->getParent() === null);
        $instanceLayer->setToggle($sourceLayer->getParent() === null);
        $instanceLayer->setParent($parent);
        return $instanceLayer;
    }

    public function getFormTemplate(SourceInstance $instance): string
    {
        return '@MapbenderWmts/Repository/instance-wmts.html.twig';
    }

    public function getFormType(SourceInstance $instance): string
    {
        return WmtsInstanceType::class;
    }
}
