<?php


namespace Mapbender\WmtsBundle\Component\Presenter;


use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\WmtsBundle\Component\TileMatrix;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;

abstract class ConfigGeneratorCommon extends SourceService
{
    public function canDeactivateLayer(SourceInstanceItem $layer)
    {
        return true;
    }

    public function useTunnel(SourceInstance $sourceInstance)
    {
        return false;
    }

    abstract protected function getLayerLegendConfig(SourceInstanceItem $instanceLayer);

    abstract protected function getLayerTreeOptions(SourceInstanceItem $instanceLayer, $isFakeRoot);

    /**
     * @param SourceInstance $sourceInstance
     * @return array|mixed[]|null
     */
    public function getInnerConfiguration(SourceInstance $sourceInstance)
    {
        /** @var WmtsInstance $sourceInstance */
        return array_replace(parent::getInnerConfiguration($sourceInstance), array(
            'version' => $sourceInstance->getSource()->getVersion(),
            'options' => $this->getOptionsConfiguration($sourceInstance),
            'children' => array($this->getRootLayerConfig($sourceInstance)),
            'layers' => $this->getLayerConfigs($sourceInstance),
            'tilematrixsets' => $this->getTileMatrixSetsConfiguration($sourceInstance),
        ));
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array
     */
    protected function getOptionsConfiguration(SourceInstance $sourceInstance)
    {
        return array(
            "proxy" => $sourceInstance->getProxy(),
            "opacity" => $sourceInstance->getOpacity() / 100,
        );
    }

    /**
     * @param WmtsInstance $instance
     * @return array
     */
    protected function getRootLayerConfig(SourceInstance $instance)
    {
        // create a fake root layer entity
        $rootSource = new WmtsLayerSource();
        $rootSource->setSource($instance->getSource());
        $rootInst = new WmtsInstanceLayer();
        $rootInst->setTitle($instance->getTitle());
        $rootInst->setSourceItem($rootSource);
        $rootInst->setId($instance->getId() . "-fake-root");
        $rootInst->setSourceInstance($instance);
        return $this->formatInstanceLayer($rootInst, true);
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array[][]
     */
    protected function getLayerConfigs($sourceInstance)
    {
        $layerConfigs = array();
        foreach ($sourceInstance->getLayers() as $layer) {
            if ($layer->getActive()) {
                $layerConfigs[] = $this->formatInstanceLayer($layer, false);
            }
        }
        return $layerConfigs;
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     * @param bool $isFakeRoot
     * @return array
     */
    protected function formatInstanceLayer(SourceInstanceItem $instanceLayer, $isFakeRoot)
    {
        $config = array(
            "options" => $this->formatInstanceLayerOptions($instanceLayer, $isFakeRoot),
            "state" => array(
                "visibility" => null,
                "info" => null,
                "outOfScale" => null,
                "outOfBounds" => null,
            ),
        );
        return $config;
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     */
    protected function formatInstanceLayerOptions(SourceInstanceItem $instanceLayer)
    {
        $sourceItem      = $instanceLayer->getSourceItem();
        $layerId = strval($instanceLayer->getId());
        if (!$layerId) {
            throw new \LogicException("Cannot safely generate config for " . get_class($instanceLayer) . " without an id");
        }
        $configuration = array(
            "id" => $layerId,
            'tileUrls' => array(),
            "title" => $instanceLayer->getTitle(),
            "identifier" => $instanceLayer->getSourceItem()->getIdentifier(),
            'matrixLinks' => array(),
        );
        foreach ($sourceItem->getTilematrixSetlinks() as $tmsl) {
            $configuration['matrixLinks'][] = $tmsl->getTileMatrixSet();
        }

        foreach ($sourceItem->getTileResources() as $ru) {
            $configuration['tileUrls'][] = $this->formatTileUrl($instanceLayer, $ru->getTemplate());
        }
        $legendConfig = $this->getLayerLegendConfig($instanceLayer);
        if ($legendConfig) {
            $configuration['legend'] = $legendConfig;
        }
        $configuration['treeOptions'] = $this->getLayerTreeOptions($instanceLayer, false);
        $bboxConfigs = array();
        $bbox = $sourceItem->getLatlonBounds();
        if ($bbox) {
            $bboxConfigs[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        $configuration['bbox'] = $bboxConfigs;

        return $configuration;
    }

    protected function formatTileUrl(WmtsInstanceLayer $instanceLayer, $url)
    {
        if ($instanceLayer->getSourceInstance()->getProxy()) {
            $url = $this->proxifyTileUrlTemplate($url);
        }
        return $url;
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array[]
     */
    protected function getTileMatrixSetsConfiguration($sourceInstance)
    {
        $configs = array();
        foreach ($sourceInstance->getSource()->getTilematrixsets() as $tilematrixset) {
            $configs[] = $this->formatTileMatrixSet($tilematrixset);
        }
        return $configs;
    }

    protected function formatTileMatrixSet(TileMatrixSet $tilematrixset)
    {
        $tilematrices = $tilematrixset->getTilematrices();
        $origin = $tilematrices[0]->getTopleftcorner();
        $tilewidth = $tilematrices[0]->getTilewidth();
        $tileheight = $tilematrices[0]->getTileheight();
        $srsCodes = $this->getSrsAliases($tilematrixset->getSupportedCrs());
        $config = array(
            'tileSize' => array($tilewidth, $tileheight),
            'identifier' => $tilematrixset->getIdentifier(),
            'supportedCrs' => $srsCodes,
            'origin' => $origin,
            'tilematrices' => array(),
        );
        foreach ($tilematrixset->getTilematrices() as $tilematrix) {
            $config['tilematrices'][] = $this->formatTileMatrix($tilematrix);
        }
        return $config;
    }

    protected function formatTileMatrix(TileMatrix $tilematrix)
    {
        return array(
            'identifier' => $tilematrix->getIdentifier(),
            'scaleDenominator' => $tilematrix->getScaledenominator(),
            'tileWidth' => $tilematrix->getTilewidth(),
            'tileHeight' => $tilematrix->getTileheight(),
            'topLeftCorner' => $tilematrix->getTopleftcorner(),
            'matrixSize' =>  array($tilematrix->getMatrixwidth(), $tilematrix->getMatrixheight()),
        );
    }

    /**
     * @param string $urlTemplate
     * @return string
     */
    protected function proxifyTileUrlTemplate($urlTemplate)
    {
        $proxyUrlInitial = $this->urlProcessor->proxifyUrl($urlTemplate);
        // Restore unencoded template placeholders
        return strtr($proxyUrlInitial, array(
            '%7B' => '{',
            '%7D' => '}',
        ));
    }

    /**
     * @param string $urnOrCode
     * @return string[]
     */
    protected function getSrsAliases($urnOrCode)
    {
        $code = $this->urnToSrsCode($urnOrCode);
        $equivalenceGroups = array(
            array(
                'EPSG:3857',
                'EPSG:900913',
                'OSGEO:41001',
            ),
        );
        foreach ($equivalenceGroups as $group) {
            if (in_array($code, $group)) {
                return $group;
            }
        }
        return array($code);
    }

    /**
     * @param string $urnOrCode
     * @return string
     */
    public static function urnToSrsCode($urnOrCode)
    {
        return preg_replace('#^urn:.*?:([A-Z]+):.*?(\d+)$#', '$1:$2', $urnOrCode);
    }
}
