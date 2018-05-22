<?php

namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Entity\Layerset;
use Mapbender\WmsBundle\Component\Wms\Importer;
use Mapbender\WmsBundle\Component\WmsInstanceEntityHandler;
use Mapbender\WmsBundle\Component\WmsInstanceLayerEntityHandler;
use Mapbender\WmsBundle\Component\WmsSourceEntityHandler;
use Mapbender\WmsBundle\Entity\WmsInstance;
use Mapbender\WmsBundle\Entity\WmsOrigin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * WmsLoader
 *
 * @author Karim Malhas
 * @author Paul Schmidt
 */
class WmsLoader extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.wms.wmsloader.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.wms.wmsloader.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array("mb.wms.wmsloader.wms", "mb.wms.wmsloader.loader");
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "tooltip" => "",
            "target" => null,
            "autoOpen" => false,
            "defaultFormat" => "image/png",
            "defaultInfoFormat" => "text/html",
            "splitLayers" => false,
            "useDeclarative" => false
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbWmsloader';
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        $files = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                'mapbender.element.wmsloader.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.distpatcher.js'),
            'css' => array('@MapbenderWmsBundle/Resources/public/sass/element/wmsloader.scss'),
            'trans' => array('MapbenderWmsBundle:Element:wmsloader.json.twig'));
        return $files;
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        if ($this->container->get('request')->get('wms_url')) {
            $wms_url = $this->container->get('request')->get('wms_url');
            $all = $this->container->get('request')->query->all();
            foreach ($all as $key => $value) {
                if (strtolower($key) === "version" && stripos($wms_url, "version") === false) {
                    $wms_url .= "&version=" . $value;
                } elseif (strtolower($key) === "request" && stripos($wms_url, "request") === false) {
                    $wms_url .= "&request=" . $value;
                } elseif (strtolower($key) === "service" && stripos($wms_url, "service") === false) {
                    $wms_url .= "&service=" . $value;
                }
            }
            $configuration['wms_url'] = urldecode($wms_url);
        }
        if ($this->container->get('request')->get('wms_id')) {
            $wmsId = $this->container->get('request')->get('wms_id');
            $configuration['wms_id'] = $wmsId;
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        $files = self::listAssets();

        $config = $this->getConfiguration();
        if (!(isset($config['useDeclarative']) && $config['useDeclarative'] === true)) {
            $idx = array_search('@MapbenderCoreBundle/Resources/public/mapbender.distpatcher.js', $files['js']);
            unset($files['js'][$idx]);
        }
        return $files;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\WmsBundle\Element\Type\WmsLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmsBundle:ElementAdmin:wmsloader.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
            ->render('MapbenderWmsBundle:Element:wmsloader.html.twig', array(
                'id' => $this->getId(),
                "title" => $this->getTitle(),
                'example_url' => $this->container->getParameter('wmsloader.example_url'),
                'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch ($action) {
            case 'loadWms':
                return $this->loadWms();
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    protected function loadWms()
    {
        $request = $this->container->get('request');
        $wmsSource = $this->getWmsSource($request);

        $wmsSourceEntityHandler = new WmsSourceEntityHandler($this->container, $wmsSource);
        $layerset = new Layerset();
        $wmsInstance = $wmsSourceEntityHandler->createInstance($layerset);

        $wmsInstanceEntityHandler = new WmsInstanceEntityHandler($this->container, $wmsInstance);
        $signer = $this->container->get('signer');
        $layerConfiguration = array();
        $layerConfiguration['title'] = $wmsInstance->getTitle();
        $layerConfiguration['type'] = strtolower($wmsInstance->getType());
        $layerConfiguration['configuration'] = $wmsInstanceEntityHandler->getConfiguration($signer);
        $layerConfiguration['configuration']['children'] = $this->getLayersetConfig($wmsInstance);

        return new JsonResponse($layerConfiguration);
    }

    protected function getWmsSource($request)
    {
        $requestUrl = $request->get("url");
        $requestUserName = $request->get("username");
        $requestPassword = $request->get("password");
        $onlyValid = $request->get("onlyvalid");

        $wmsOrigin = new WmsOrigin($requestUrl, $requestUserName, $requestPassword);
        /** @var Importer $importer */
        $importer = $this->container->get('mapbender.importer.source.wms.service');
        $importerResponse = $importer->evaluateServer($wmsOrigin, $onlyValid);

        return $importerResponse->getWmsSourceEntity();
    }

    protected function getLayersetConfig(WmsInstance $wmsInstance)
    {
        $wmsInstanceLayers = $wmsInstance->getLayers();
        $layersetConfiguration = array();

        foreach ($wmsInstanceLayers as $layer) {
            $instHandler = new WmsInstanceLayerEntityHandler($this->container, $layer);
            $conf        = $instHandler->generateConfiguration();
            array_push($layersetConfiguration, $conf);

            if (!$conf) {
                continue;
            }

            $layerSets[] = array(
                $layer->getId() => array(
                    'type'          => strtolower($wmsInstance->getType()),
                    'title'         => $layer->getTitle(),
                    'configuration' => $conf
                )
            );
        }
        return $layersetConfiguration;
    }
}
