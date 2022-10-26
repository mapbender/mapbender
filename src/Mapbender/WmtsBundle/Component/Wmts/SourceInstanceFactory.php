<?php


namespace Mapbender\WmtsBundle\Component\Wmts;


use Mapbender\CoreBundle\Entity\Source;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Mapbender\WmtsBundle\Entity\WmtsSource;
use Mapbender\WmtsBundle\Form\Type\TmsInstanceType;
use Mapbender\WmtsBundle\Form\Type\WmtsInstanceType;

class SourceInstanceFactory implements \Mapbender\Component\SourceInstanceFactory
{
    public function createInstance(Source $source)
    {
        /** @var WmtsSource $source */
        $instance = new WmtsInstance();
        $instance->setSource($source);
        $instance->setTitle($source->getTitle());

        foreach ($source->getLayers() as $layer) {
            $instLayer = $this->createInstanceLayer($layer);
            $instance->addLayer($instLayer);
        }
        // avoid persistence errors (non-nullable column)
        $instance->setWeight(0);
        return $instance;
    }

    public static function createInstanceLayer(WmtsLayerSource $sourceLayer)
    {
        $instanceLayer = new WmtsInstanceLayer();
        $instanceLayer->setSourceItem($sourceLayer);
        $instanceLayer->setTitle($sourceLayer->getTitle());
        // Wmts only
        $infoFormats = array_values(array_filter($sourceLayer->getInfoformats() ?: array()));
        if ($infoFormats) {
            $instanceLayer->setInfoformat($infoFormats[0]);
            $instanceLayer->setInfo(true);
            $instanceLayer->setAllowinfo(true);
        }
        // Wmts only
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
        return $instanceLayer;
    }

    public function fromConfig(array $data, $id)
    {
        throw new \RuntimeException("Yaml-defined Wmts sources not implemented");
    }

    public function matchInstanceToPersistedSource(SourceInstance $instance, array $extraSources)
    {
        throw new \RuntimeException("Yaml-defined Wmts sources not implemented");
    }

    public function getFormTemplate(SourceInstance $instance)
    {
        $sourceType = $instance->getSource()->getType();
        switch ($sourceType) {
            default:
                throw new \InvalidArgumentException("Unhandled source type " . \var_export($sourceType, true));
            case Source::TYPE_WMTS:
                return '@MapbenderWmts/Repository/instance-wmts.html.twig';
            case Source::TYPE_TMS:
                return '@MapbenderWmts/Repository/instance-tms.html.twig';
        }
    }

    public function getFormType(SourceInstance $instance)
    {
        $sourceType = $instance->getSource()->getType();
        switch ($sourceType) {
            default:
                throw new \InvalidArgumentException("Unhandled source type " . \var_export($sourceType, true));
            case Source::TYPE_WMTS:
                return WmtsInstanceType::class;
            case Source::TYPE_TMS:
                return TmsInstanceType::class;
        }
    }
}
