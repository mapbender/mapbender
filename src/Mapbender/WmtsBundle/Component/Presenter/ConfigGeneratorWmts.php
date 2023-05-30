<?php


namespace Mapbender\WmtsBundle\Component\Presenter;



use Mapbender\CoreBundle\Entity\Application;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;

class ConfigGeneratorWmts extends ConfigGeneratorCommon
{

    public function canDeactivateLayer(SourceInstanceItem $layer)
    {
        return true;
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     */
    protected function getLayerTreeOptions(SourceInstanceItem $instanceLayer, $isFakeRoot)
    {
        return array(
            "info" => $instanceLayer->getInfoformat() && $instanceLayer->getInfo(),
            "selected" => $instanceLayer->getSelected(),
            "toggle" => false,
            "allow" => array(
                "info" => $instanceLayer->getInfoformat() && $instanceLayer->getAllowinfo(),
                "selected" => $instanceLayer->getAllowselected(),
                "toggle" => false,
                "reorder" => null,
            ),
        );
    }

    protected function formatTileUrl(WmtsInstanceLayer $instanceLayer, $url)
    {
        $style = $instanceLayer->getStyle();
        // Spec unclear about capitalization => do both
        $url = \str_replace('{Style}', $style, $url);
        $url = \str_replace('{style}', $style, $url);
        return $url;
    }

    /**
     * Return the client-facing configuration for a layer's legend
     *
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     */
    protected function getLayerLegendConfig(SourceInstanceItem $instanceLayer)
    {
        // @todo: tunnel support
        $legendHref = $this->getInternalLegendUrl($instanceLayer);
        if ($legendHref) {
            /** @var WmtsInstance $sourceInstance */
            $sourceInstance = $instanceLayer->getSourceInstance();
            if ($sourceInstance->getProxy()) {
                $legendHref = $this->urlProcessor->proxifyUrl($legendHref);
            }
            return array(
                'url' => $legendHref,
            );
        }
        return array();
    }

    public function getInternalLegendUrl(SourceInstanceItem $instanceLayer)
    {
        /** @var WmtsInstanceLayer $instanceLayer */
        foreach ($instanceLayer->getSourceItem()->getStyles() as $style) {
            $sourceStyle = $instanceLayer->getStyle();
            if (!$sourceStyle || $sourceStyle === $style->getIdentifier()) {
                if ($style->getLegendurl()) {
                    return $style->getLegendurl()->getHref() ?: null;
                }
            }
        }
        return null;
    }

    public function getScriptAssets(Application $application)
    {
        return array(
            '@MapbenderCoreBundle/Resources/public/mapbender.geosource.js',
            '@MapbenderWmtsBundle/Resources/public/geosource-base.js',
            '@MapbenderWmtsBundle/Resources/public/mapbender.geosource.wmts.js',
        );
    }
}
