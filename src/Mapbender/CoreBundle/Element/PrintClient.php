<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Utils\UrlUtil;
use Mapbender\PrintBundle\Component\OdgParser;
use Mapbender\PrintBundle\Component\Service\PrintServiceBridge;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderPrintBundle/Resources/public/mapbender.element.imageExport.js',
                'mapbender.element.printClient.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/printclient.scss',
            ),
            'trans' => array(
                'MapbenderCoreBundle:Element:printclient.json.twig',
            ),
        );
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
            'required_fields_first' => false,
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

    public function getFrontendTemplateVars()
    {
        $config = $this->getConfiguration() + array(
            'required_fields_first' => false,
        );
        $router = $this->container->get('router');
        $submitUrl = $router->generate('mapbender_core_application_element', array(
            'slug' => $this->entity->getApplication()->getSlug(),
            'id' => $this->entity->getId(),
            'action' => 'print',
        ));
        return array(
            'configuration' => $config,
            'submitUrl' => $submitUrl,
            'formTarget' => '_blank',
        );
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return "MapbenderCoreBundle:Element:printclient{$suffix}";
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var Request $request */
        $request = $this->container->get('request');
        $bridgeService = $this->getServiceBridge();
        $configuration = $this->getConfiguration();
        switch ($action) {
            case 'print':
                $data = $this->preparePrintData($request, $configuration);

                $pdfBody = $bridgeService->buildPdf($data);

                $displayInline = true;

                if(array_key_exists('file_prefix', $configuration)) {
                    $filename = $configuration['file_prefix'] . '_' . date("YmdHis") . '.pdf';
                } else {
                    $filename = 'mapbender_print.pdf';
                }
                $response = new Response($pdfBody, 200, array(
                    'Content-Type' => $displayInline ? 'application/pdf' : 'application/octet-stream',
                    'Content-Disposition' => 'attachment; filename=' . $filename
                ));

                return $response;

            case 'getTemplateSize':
                $template = $request->get('template');
                $odgParser = new OdgParser($this->container);
                $size = $odgParser->getMapSize($template);

                return new Response($size);

            default:
                $response = $bridgeService->handleHttpRequest($request);
                if ($response) {
                    return $response;
                } else {
                    throw new NotFoundHttpException();
                }
        }
    }

    /**
     * @return PrintServiceBridge
     */
    protected function getServiceBridge()
    {
        /** @var PrintServiceBridge $bridgeService */
        $bridgeService = $this->container->get('mapbender.print_service_bridge.service');
        return $bridgeService;
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
        if (isset($data['data'])) {
            $d0 = $data['data'];
            unset($data['data']);
            $data = array_replace($data, json_decode($d0, true));
        }
        $urlProcessor = $this->getUrlProcessor();
        foreach ($data['layers'] as $ix => $layerDef) {
            if (!empty($layerDef['url'])) {
                $updatedUrl = $urlProcessor->getInternalUrl($layerDef['url']);
                if (!isset($configuration['replace_pattern'])) {
                    if ($data['quality'] != 72) {
                        $updatedUrl = UrlUtil::validateUrl($updatedUrl, array(
                            'map_resolution' => $data['quality'],
                        ));
                    }
                } else {
                    $updatedUrl = $this->addReplacePattern($updatedUrl, $configuration['replace_pattern'], $data['quality']);
                }
                $data['layers'][$ix]['url'] = $updatedUrl;
            }
        }

        if (isset($data['overview'])) {
            $data['overview'] = $this->prepareOverview($data['overview']);
        }

        if (isset($data['legends'])) {
            $data['legends'] = $this->prepareLegends($data['legends']);
        }
        $data['user'] = $this->getUser();
        return $data;
    }

    protected function prepareOverview($overviewDef)
    {
        if (!empty($overviewDef['layers'])) {
            $urlProcessor = $this->getUrlProcessor();
            foreach ($overviewDef['layers'] as $index => $url) {
                $overviewDef['layers'][$index] = $urlProcessor->getInternalUrl($url);
            }
        }
        return $overviewDef;
    }

    /**
     * Apply "replace_pattern" backend configuration to given $url, either
     * rewriting a part of it or appending something, depending on $dpi
     * value.
     *
     * @param string $url
     * @param array $rplConfig
     * @param int $dpi
     * @return string updated $url
     */
    protected function addReplacePattern($url, $rplConfig, $dpi)
    {
        foreach ($rplConfig as $pattern) {
            if (isset($pattern['default'][$dpi])) {
                return $url . $pattern['default'][$dpi];
            } elseif (strpos($url, $pattern['pattern']) !== false) {
                if (isset($pattern['replacement'][$dpi])){
                    return str_replace($pattern['pattern'], $pattern['replacement'][$dpi], $url);
                }
            }
        }
        // no match, no change
        return $url;
    }

    /**
     * @param array[] $legendDefs
     * @return string[]
     */
    protected function prepareLegends($legendDefs)
    {
        $urlProcessor = $this->getUrlProcessor();
        $legendDefsOut = array();
        foreach ($legendDefs as $ix => $imageList) {
            $legendDefsOut[$ix] = array();
            foreach ($imageList as $title => $legendImageUrl) {
                $internalUrl = $urlProcessor->getInternalUrl($legendImageUrl);
                $legendDefsOut[$ix][$title] = $internalUrl;
            }
        };
        return $legendDefsOut;
    }

    /**
     * @return UrlProcessor
     */
    protected function getUrlProcessor()
    {
        /** @var UrlProcessor $service */
        $service = $this->container->get('mapbender.source.url_processor.service');
        return $service;
    }
}
