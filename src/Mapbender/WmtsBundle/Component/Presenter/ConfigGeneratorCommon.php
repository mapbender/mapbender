<?php


namespace Mapbender\WmtsBundle\Component\Presenter;


use Mapbender\CoreBundle\Component\Source\SourceInstanceConfigGenerator;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\CoreBundle\Entity\SourceInstanceItem;
use Mapbender\WmtsBundle\Component\TileMatrix;
use Mapbender\WmtsBundle\Entity\TileMatrixSet;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;

abstract class ConfigGeneratorCommon extends SourceInstanceConfigGenerator
{
    public function __construct(
        protected UrlProcessor $urlProcessor,
    )
    {
    }

    abstract protected function getLayerLegendConfig(SourceInstanceItem $instanceLayer);

    abstract protected function getLayerTreeOptions(SourceInstanceItem $instanceLayer);


    public function getConfiguration(SourceInstance $sourceInstance, ?string $idPrefix = null): array
    {
        /** @var WmtsInstance $sourceInstance */
        return array_merge(parent::getConfiguration($sourceInstance, $idPrefix), [
            'version' => $sourceInstance->getSource()->getVersion(),
            'options' => $this->getOptionsConfiguration($sourceInstance),
            'children' => $this->getRootLayerConfig($sourceInstance),
            'tilematrixsets' => $this->getTileMatrixSetsConfiguration($sourceInstance),
        ]);
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
        $rootLayers = [];
        foreach ($instance->getLayers() as $layer) {
            if ($layer->getParent() !== null) continue;
            $rootLayers[] = $this->formatInstanceLayer($layer);
        }

        return $rootLayers;
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
                $layerConfigs[] = $this->formatInstanceLayer($layer);
            }
        }
        return $layerConfigs;
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     */
    protected function formatInstanceLayer(SourceInstanceItem $instanceLayer): array
    {
        $children = [];
        foreach ($instanceLayer->getSublayer() as $child) {
            if ($child->getActive()) $children[] = $this->formatInstanceLayer($child);
        }

        return [
            "options" => $this->formatInstanceLayerOptions($instanceLayer),
            "state" => [
                "visibility" => $instanceLayer->getActive(),
                "info" => null,
                "outOfScale" => null,
                "outOfBounds" => null,
            ],
            "children" => $children,
        ];
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     */
    protected function formatInstanceLayerOptions(SourceInstanceItem $instanceLayer)
    {
        $sourceItem = $instanceLayer->getSourceItem();
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

        $getTile = $sourceItem->getSource()->getGetTile();
        if ($getTile?->getHttpGetKvp()) {
            // Key-Value-Pair URL (similar to WMS), construct url with template GET parameters
            $configuration['tileUrls'][] = $this->formatTileUrl($instanceLayer, $getTile->getHttpGetKvp())
                . "Version=" . $sourceItem->getSource()->getVersion()
                . "&Layer=" . $sourceItem->getIdentifier()
                . "&Format=image/png"
                . "&Service=WMTS"
                . "&Request=GetTile"
                . "&TileMatrixSet={TileMatrixSet}"
                . "&TileMatrix={TileMatrix}"
                . "&TileRow={TileRow}"
                . "&TileCol={TileCol}";

        } elseif ($getTile?->getHttpGetRestful()) {
            // RESTful URL (template URL with path parameters already included in URL [hopefully])
            $configuration['tileUrls'][] = $this->formatTileUrl($instanceLayer, $getTile->getHttpGetRestful());
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
        $tileMatrices = $tilematrixset->getTilematrices();
        $origin = $tileMatrices[0]->getTopleftcorner();

        // Some services use [-180 90], some [90 -180] as topLeftCorner for EPSG:4326
        // there seems to be no convention, so set it fix to the order OpenLayers requires
        // if someone finds a better solution, have fun
        if ($tilematrixset->getSupportedCrs() === "EPSG:4326") {
            foreach ($tileMatrices as $tilematrix) {
                $tilematrix->setTopleftcorner([-180, 90]);
            }
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            $origin = $tileMatrices[0]->getTopleftcorner();
        }

        $tileWidth = $tileMatrices[0]->getTilewidth();
        $tileHeight = $tileMatrices[0]->getTileheight();
        $srsCodes = $this->getSrsAliases($tilematrixset->getSupportedCrs());
        $config = array(
            'tileSize' => array($tileWidth, $tileHeight),
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
            'matrixSize' => array($tilematrix->getMatrixwidth(), $tilematrix->getMatrixheight()),
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
