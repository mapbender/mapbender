<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\PrintBundle\Component\OdgParser;
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
            "file_prefix" => 'mapbender3'
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
        $request = $this->container->get('request');
        $configuration = $this->getConfiguration();
        switch ($action) {
            case 'print':

                $data = $request->request->all();

                foreach ($data['layers'] as $idx => $layer) {
                    $data['layers'][$idx] = json_decode($layer, true);
                }

                if (isset($data['overview'])) {
                    foreach ($data['overview'] as $idx => $layer) {
                        $data['overview'][$idx] = json_decode($layer, true);
                    }
                }

                if (isset($data['features'])) {
                    foreach ($data['features'] as $idx => $value) {
                        $data['features'][$idx] = json_decode($value, true);
                    }
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
                    $data['legends'] = json_decode($data['legends'], true);
                }

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
}
