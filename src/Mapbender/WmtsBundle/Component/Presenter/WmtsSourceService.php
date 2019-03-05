<?php


namespace Mapbender\WmtsBundle\Component\Presenter;



use Mapbender\CoreBundle\Component\Presenter\SourceService;
use Mapbender\CoreBundle\Entity\SourceInstance;
use Mapbender\WmtsBundle\Component\WmtsInstanceLayerEntityHandler;
use Mapbender\WmtsBundle\Entity\WmtsInstance;
use Mapbender\WmtsBundle\Entity\WmtsInstanceLayer;
use Mapbender\WmtsBundle\Entity\WmtsLayerSource;
use Symfony\Component\DependencyInjection\ContainerInterface;

class WmtsSourceService extends SourceService
{

    /** @var WmtsInstanceLayerEntityHandler */
    protected $instanceLayerHandler;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->instanceLayerHandler = new WmtsInstanceLayerEntityHandler($container, null);
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array|mixed[]|null
     */
    public function getInnerConfiguration(SourceInstance $sourceInstance)
    {
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
    public function getOptionsConfiguration($sourceInstance)
    {
        return array(
            "proxy" => $sourceInstance->getProxy(),
            "visible" => $sourceInstance->getVisible(),
            "opacity" => $sourceInstance->getOpacity() / 100,
        );
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array
     */
    protected function getRootLayerConfig($sourceInstance)
    {
        // create a fake root layer entity
        $rootSource = new WmtsLayerSource();
        $rootSource->setSource($sourceInstance->getSource());
        $rootInst = new WmtsInstanceLayer();
        $rootInst->setTitle($sourceInstance->getRoottitle());
        $rootInst->setSourceItem($rootSource);
        $rootInst->setId($sourceInstance->getId() . "-fake-root");
        $rootInst->setSourceInstance($sourceInstance);
        $rootInst->setActive($sourceInstance->getActive())
            ->setAllowinfo($sourceInstance->getAllowinfo())
            ->setInfo($sourceInstance->getInfo())
            ->setAllowtoggle($sourceInstance->getAllowtoggle())
            ->setToggle($sourceInstance->getToggle())
        ;
        return $this->getSingleLayerConfig($rootInst);
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
                $layerConfigs[] = $this->getSingleLayerConfig($layer);
            }
        }
        return $layerConfigs;
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     */
    public function getSingleLayerConfig($instanceLayer)
    {
        $config = array(
            "options" => $this->getSingleLayerOptionsConfig($instanceLayer),
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
    protected function getSingleLayerOptionsConfig($instanceLayer)
    {
        $sourceItem      = $instanceLayer->getSourceItem();
        $resourceUrl     = $sourceItem->getResourceUrl();
        $layerId = strval($instanceLayer->getId());
        if (!$layerId) {
            throw new \LogicException("Cannot safely generate config for " . get_class($instanceLayer) . " without an id");
        }
        $useProxy = !!$instanceLayer->getSourceInstance()->getProxy();
        $configuration   = array(
            "id" => $layerId,
            "origId" => $layerId,
            'tileUrls' => array(),
            'format' => null,
            "title" => $instanceLayer->getTitle(),
            "style" => $instanceLayer->getStyle(),
            "identifier" => $instanceLayer->getSourceItem()->getIdentifier(),
            "tilematrixset" => $instanceLayer->getTileMatrixSet(),
        );

        foreach ($sourceItem->getResourceUrl() as $ru) {
            $resourceType = $ru->getResourceType() ?: 'tile';   // NOTE: TMS seems to have nulls here
            if ($resourceType == 'tile') {
                $urlTemplate = $ru->getTemplate();
                if ($useProxy) {
                    $urlTemplate = $this->proxifyTileUrlTemplate($urlTemplate);
                }
                $configuration['tileUrls'][] = $urlTemplate;
                if (!$configuration['format'] && $ru->getFormat()) {
                    $configuration['format'] = $ru->getFormat();
                }
            }

            $configuration['cheese'][] = array(
                'format' => $ru->getFormat(),
                'rt' => $ru->getResourceType(),
                'template' => $ru->getTemplate(),
            );
        }
        $legendConfig = $this->getLayerLegendConfig($instanceLayer);
        if ($legendConfig) {
            $configuration['legend'] = $legendConfig;
        }
        $configuration['treeOptions'] = $this->getSingleLayerTreeOptionsConfig($instanceLayer);
        $srses = array();
        foreach ($sourceItem->getMergedBoundingBoxes() as $bbox) {
            $srses[$bbox->getSrs()] = $bbox->toCoordsArray();
        }
        $configuration['bbox'] = $srses;

        return $configuration;
    }

    /**
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     * @todo this seems to be universal and should go down into SourceService
     */
    protected function getSingleLayerTreeOptionsConfig($instanceLayer)
    {
        // TODO check if layers support info
        return array(
            "info" => $instanceLayer->getInfo(),
            "selected" => $instanceLayer->getSelected(),
            "toggle" => $instanceLayer->getToggle(),
            "allow" => array(
                "info" => $instanceLayer->getAllowinfo(),
                "selected" => $instanceLayer->getAllowselected(),
                "toggle" => $instanceLayer->getAllowtoggle(),
                "reorder" => null,
            ),
        );
    }

    /**
     * @param WmtsInstance $sourceInstance
     * @return array[]
     */
    protected function getTileMatrixSetsConfiguration($sourceInstance)
    {
        $configs = array();
        foreach ($sourceInstance->getSource()->getTilematrixsets() as $tilematrixset) {
            $tilematrices = $tilematrixset->getTilematrices();
            $origin = $tilematrices[0]->getTopleftcorner();
            $tilewidth = $tilematrices[0]->getTilewidth();
            $tileheight = $tilematrices[0]->getTileheight();
            $tilematricesArr = array();
            foreach ($tilematrices as $tilematrix) {
                $tilematricesArr[] = array(
                    'identifier' => $tilematrix->getIdentifier(),
                    'scaleDenominator' => $tilematrix->getScaledenominator(),
                    'tileWidth' => $tilematrix->getTilewidth(),
                    'tileHeight' => $tilematrix->getTileheight(),
                    'topLeftCorner' => $tilematrix->getTopleftcorner(),
                    'matrixSize' =>  array($tilematrix->getMatrixwidth(), $tilematrix->getMatrixheight())
                );
            }
            $supportedCrs = $tilematrixset->getSupportedCrs();
            $supportedCrs = preg_replace('#^urn:.*?:([\A-Z]+):.*?(\d+)$#', '$1:$2', $supportedCrs);
            $supportedCrs = strtr($supportedCrs, array(
                'OSGEO:41001' => 'EPSG:900913',
            ));
            $configs[] = array(
                'id' => $tilematrixset->getId(),
                'tileSize' => array($tilewidth, $tileheight),
                'identifier' => $tilematrixset->getIdentifier(),
                'supportedCrs' => $supportedCrs,
                'origin' => $origin,
                'tilematrices' => $tilematricesArr
            );
        }
        return $configs;
    }

    /**
     * Return the client-facing configuration for a layer's legend
     *
     * @param WmtsInstanceLayer $instanceLayer
     * @return array
     */
    protected function getLayerLegendConfig($instanceLayer)
    {
        // @todo: tunnel support
        foreach ($instanceLayer->getSourceItem()->getStyles() as $style) {
            $sourceStyle = $instanceLayer->getStyle();
            if (!$sourceStyle || $sourceStyle === $style->getIdentifier()) {
                if ($style->getLegendurl()) {
                    $legendHref = $style->getLegendurl()->getHref();
                    /** @var WmtsInstance $sourceInstance */
                    $sourceInstance = $instanceLayer->getSourceInstance();
                    if ($sourceInstance->getProxy()) {
                        $legendHref = $this->urlProcessor->proxifyUrl($legendHref);
                    }
                    return array(
                        'url' => $legendHref,
                    );
                }
            }
        }
        return array();
    }

    /**
     * @param string $urlTemplate
     * @return string
     */
    protected function proxifyTileUrlTemplate($urlTemplate)
    {
        // add dummy 'service' query param for owsproxy...
        // @todo: remove service type 'intelligence' from owsproxy
        $dummyServiceParam = 'service=WMS';
        if (false !== strpos($urlTemplate, '?')) {
            $urlWithService = "{$urlTemplate}&{$dummyServiceParam}";
        } else {
            $urlWithService = "{$urlTemplate}?{$dummyServiceParam}";
        }
        $proxyUrlInitial = $this->urlProcessor->proxifyUrl($urlWithService);
        // Restore unencoded template placeholders
        return strtr($proxyUrlInitial, array(
            '%7B' => '{',
            '%7D' => '}',
        ));
    }
}
