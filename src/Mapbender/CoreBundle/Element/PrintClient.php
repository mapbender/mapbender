<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\Exception\SourceNotFoundException;
use Mapbender\CoreBundle\Component\Source\Tunnel\InstanceTunnelService;
use Mapbender\PrintBundle\Component\OdgParser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Mapbender\PrintBundle\Component\PrintService;

/**
 *
 */
class PrintClient extends Element
{

    public static $merge_configurations = false;

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.printclient.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.printclient.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.printclient.tag.print",
            "mb.core.printclient.tag.pdf",
            "mb.core.printclient.tag.png",
            "mb.core.printclient.tag.gif",
            "mb.core.printclient.tag.jpg",
            "mb.core.printclient.tag.jpeg");
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array('js' => array('mapbender.element.printClient.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/printclient.scss'),
            'trans' => array('MapbenderCoreBundle:Element:printclient.json.twig'));
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null,
            "templates" => array(
                array(
                    'template' => "a4portrait",
                    "label" => "A4 Portrait")
                ,
                array(
                    'template' => "a4landscape",
                    "label" => "A4 Landscape")
                ,
                array(
                    'template' => "a3portrait",
                    "label" => "A3 Portrait")
                ,
                array(
                    'template' => "a3landscape",
                    "label" => "A3 Landscape")
                ,
                array(
                    'template' => "a4_landscape_offical",
                    "label" => "A4 Landscape offical"),
                array(
                    'template' => "a2_landscape_offical",
                    "label" => "A2 Landscape offical")
            ),
            "scales" => array(500, 1000, 5000, 10000, 25000),
            "quality_levels" => array(array('dpi' => "72", 'label' => "Draft (72dpi)"),
                array('dpi' => "288", 'label' => "Document (288dpi)")),
            "rotatable" => true,
            "legend" => true,
            "legend_default_behaviour" => true,
            "optional_fields" => array(
                "title" => array("label" => 'Title', "options" => array("required" => false)),
                "comment1" => array("label" => 'Comment 1', "options" => array("required" => false)),
                "comment2" => array("label" => 'Comment 2', "options" => array("required" => false))),
            "replace_pattern" => null,
            "file_prefix" => 'mapbender'
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();
        if (isset($config["templates"])) {
            $templates = array();
            foreach ($config["templates"] as $template) {
                $templates[$template['template']] = $template;
            }
            $config["templates"] = $templates;
        }
        if (isset($config["quality_levels"])) {
            $levels = array();
            foreach ($config["quality_levels"] as $level) {
                $levels[$level['dpi']] = $level['label'];
            }
            $config["quality_levels"] = $levels;
        }
        if (!isset($config["type"])) {
            $config["type"] = "dialog";
        }
        return $config;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\PrintClientAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:printclient.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbPrintClient';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
            'MapbenderCoreBundle:Element:printclient.html.twig',
            array(
                'id' => $this->getId(),
                'title' => $this->getTitle(),
                'configuration' => $this->getConfiguration()
            )
        );
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var Request $request */
        $request = $this->container->get('request');
        $configuration = $this->getConfiguration();
        switch ($action) {
            case 'print':
                $data = $this->preparePrintData($request, $configuration);
                $printservice = $this->getPrintService();

                $displayInline = true;
                $filename = 'mapbender_print.pdf';
                if(array_key_exists('file_prefix', $configuration)) {
                    $filename = $configuration['file_prefix'] . '_' . date("YmdHis") . '.pdf';
                }
                $response = new Response($printservice->doPrint($data), 200, array(
                    'Content-Type' => $displayInline ? 'application/pdf' : 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename=' . $filename
                ));

                return $response;

            case 'getTemplateSize':
                $template = $request->get('template');
                $odgParser = new OdgParser($this->container);
                $size = $odgParser->getMapSize($template);

                return new Response($size);

            case 'getDigitizerTemplates':
                $featureType = $request->get('schemaName');
                $featureTypeConfig = $this->container->getParameter('featureTypes');
                $templates = $featureTypeConfig[$featureType]['print']['templates'];

                if (!isset($templates)) {
                    throw new \Exception('Template configuration missing');
                }

                return new JsonResponse($templates);
        }
    }

    /**
     * @return PrintService
     */
    protected function getPrintService()
    {
        $container = $this->container;
        $printServiceClassName = $container->getParameter('mapbender.print.service.class');
        return new $printServiceClassName($container);
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderCoreBundle:Element:printclient{$suffix}";
    }

    /**
     * @param Request $request
     * @param mixed[] $configuration
     * @return mixed[]
     */
    protected function preparePrintData(Request $request, $configuration)
    {
        // @todo: define what data we support; do not simply process and forward everything
        $data = $request->request->all();

        $data['layers'] = $this->prepareLayerDefinitions($data['layers']);
        if (isset($data['overview'])) {
            $data['overview'] = $this->prepareLayerDefinitions($data['overview']);
        }

        if (isset($data['features'])) {
            $data['features'] = $this->prepareFeatures($data['features']);
        }

        if (isset($configuration['replace_pattern'])) {
            foreach ($configuration['replace_pattern'] as $idx => $value) {
                $data['replace_pattern'][$idx] = $value;
            }
        }

        if (isset($data['extent_feature'])) {
            $data['extent_feature'] = json_decode($data['extent_feature'], true);
        }

        if (isset($data['legends'])) {
            $data['legends'] = $this->prepareLegends($data['legends']);
        }
        $data['user'] = $this->getUser();
        return $data;
    }

    /**
     * @param string $url
     * @return string
     */
    protected function resolveTunnelUrl($url)
    {
        /** @var InstanceTunnelService $tunnelService */
        $tunnelService = $this->container->get('mapbender.source.instancetunnel.service');
        $endPoint = $tunnelService->endpointFromUrl($url);
        if (!$endPoint) {
            return $url;
        }
        return $endPoint->getInternalUrl(Request::create($url), true);
    }

    /**
     * Trivial json-decode of input; provided as an override hook for customization.
     * Processes 'features' value from client.
     *
     * @param array|string|string[] $rawFeatureData
     * @return mixed[][]
     */
    protected function prepareFeatures($rawFeatureData)
    {
        // feature definition list is a 2+ levels nested array
        /** @var mixed[][] $featureDefs */
        $featureDefs = $this->unravelJson($rawFeatureData, 1);
        return $featureDefs;
    }

    /**
     * Trivial json-decode of input; provided as an override hook for customization.
     * Processes both 'layers' and 'overview' values from client.
     *
     * @param array|string|string[] $rawDefinitions
     * @return mixed[][]
     * @throws \InvalidArgumentException
     */
    protected function prepareLayerDefinitions($rawDefinitions)
    {
        $definitionsOut = array();
        foreach ($this->unravelJson($rawDefinitions, 2) as $layerDef) {
            // 'url' is mandatory for WMS, also mandatory for 'overview', which doesn't have a 'type',
            // and it may also be present on other layer types
            if ($layerDef['type'] == 'wms' || !empty($layerDef['url'])) {
                try {
                    $definitionsOut[] = array_replace($layerDef, array(
                        'url' => $this->resolveTunnelUrl($layerDef['url']),
                    ));
                } catch (SourceNotFoundException $e) {
                    // tunnel URL but instance not in database (anymore); skip layer completely
                    // @todo: log a warning?
                }
            } else {
                // let's hope it's a feature layer
                $definitionsOut[] = $layerDef;
            }
        }
        return $definitionsOut;
    }

    /**
     * Trivial json-decode of input; provided as an override hook for customization.
     * Processes 'features' value from client.
     *
     * @param array|string|string[] $rawLegendData
     * @return string[]
     */
    protected function prepareLegends($rawLegendData)
    {
        $processedLegendDefs = array();
        foreach ($this->unravelJson($rawLegendData, 1) as $sourceLegendDefs) {
            foreach ($sourceLegendDefs as $index => $layerDefs) {
                $processedSourceLegendDef = array();
                foreach ($layerDefs as $layerTitle => $legendUrl) {
                    try {
                        $processedSourceLegendDef[$layerTitle] = $this->resolveTunnelUrl($legendUrl);
                    } catch (SourceNotFoundException $e) {
                        // tunnel URL but instance not in database (anymore); skip layer completely
                        // @todo: log a warning?
                    }
                }
                if ($processedSourceLegendDef) {
                    $processedLegendDefs[] = $processedSourceLegendDef;
                }
            }
        }
        return $processedLegendDefs;
    }

    /**
     * Turn a json-encoded string into an array, while letting arrays pass through unchanged.
     * This can be done recursively.
     * Keys are preserved.
     *
     * @todo this could be a util; unknown if other elements or components have similar needs
     * @param string|string[] $rawInput
     * @param int $maxDepth for recursion; 0 = unlimited; 1 = top level only (default)
     * @return mixed[]
     * @throws \InvalidArgumentException if the top-level output is not an array; it's not generally safe
     *    to multi-decode json scalars
     * @throws \InvalidArgumentException if the input is neither string nor array
     */
    protected static function unravelJson($rawInput, $maxDepth = 1)
    {
        if (is_string($rawInput)) {
            $a = json_decode($rawInput, true);
            if (!is_array($a)) {
                throw new \InvalidArgumentException("Unsafe scalar type " . gettype($a));
            }
        } elseif (is_array($rawInput)) {
            $a = $rawInput;
        } else {
            throw new \InvalidArgumentException("Unhandled input type " . gettype($rawInput));
        }
        if (!$maxDepth || $maxDepth > 1) {
            foreach ($a as $k => $v) {
                try {
                    $a[$k] = static::unravelJson($v, max(0, $maxDepth - 1));
                } catch (\InvalidArgumentException $e) {
                    $a[$k] = $v;
                }
            }
        }
        return $a;
    }
}
