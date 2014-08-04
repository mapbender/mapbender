<?php
namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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
    static public function getClassTitle()
    {
        return "mb.wms.wmsloader.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wms.wmsloader.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
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
    static public function listAssets()
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
        $wms_url = $this->container->get('request')->get('wms_url');
        if ($wms_url) {
            $all = $this->container->get('request')->query->all();
            foreach ($all as $key => $value) {
                if(strtolower($key) === "version" && stripos($wms_url, "version") === false){
                    $wms_url .= "&version=" . $value;
                } else if(strtolower($key) === "request" && stripos($wms_url, "request") === false){
                    $wms_url .= "&request=" . $value;
                } else if(strtolower($key) === "service" && stripos($wms_url, "service") === false){
                    $wms_url .= "&service=" . $value;
                }
            }
            $configuration['wms_url'] = urldecode($wms_url);
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
        if(!(isset($config['useDeclarative']) && $config['useDeclarative'] === true)) {
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
                ->render('MapbenderWmsBundle:Element:wmsloader.html.twig',
                    array(
                    'id' => $this->getId(),
                    "title" => $this->getTitle(),
                    'configuration' => $this->getConfiguration()));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        //TODO ACL ACCESS
        switch ($action) {
            case 'getCapabilities':
                return $this->getCapabilities();
                break;
            case 'signeUrl':
                return $this->signeUrl();
                break;
            case 'signeSources':
                return $this->signeSources();
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }
    }

    /**
     * Returns
     *
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function getCapabilities()
    {
        $gc_url = urldecode($this->container->get('request')->get("url", null));
        $signer = $this->container->get('signer');
        $signedUrl = $signer->signUrl($gc_url);
        $data = $this->container->get('request')->get('data', null);
//        return $this->container->get('http_kernel')->forward(
//                'OwsProxy3CoreBundle:OwsProxy:entryPoint',
//                array(
//                'content' => $data
//                ),array(
//                'url' => urlencode($signedUrl)
//                )
//        );
        $path = array(
            '_controller' => 'OwsProxy3CoreBundle:OwsProxy:entryPoint',
            'url' => urlencode($signedUrl)
        );
        $subRequest = $this->container->get('request')->duplicate(
            array('url' => urlencode($signedUrl)),
//            $this->getRequest()->query->all(),
            $this->container->get('request')->request->all(),
            $path);
        return $this->container->get('http_kernel')->handle(
                $subRequest, HttpKernelInterface::SUB_REQUEST);
    }

    /**
     * Returns
     *
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function signeUrl()
    {
        $gc_url = urldecode($this->container->get('request')->get("url", null));
        $signer = $this->container->get('signer');
        $signedUrl = $signer->signUrl($gc_url);
        return new Response(json_encode(array("success" => $signedUrl)),
                200, array('Content-Type' => 'application/json'));
    }

    /**
     * Returns
     *
     * @return \Symfony\Component\HttpFoundation\Response a json encoded result.
     */
    protected function signeSources()
    {
        $sources = json_decode($this->container->get('request')->get("sources", "[]"),true);
        $signer = $this->container->get('signer');
        foreach ($sources as &$source) {
            $source['configuration']['options']['url'] = $signer->signUrl($source['configuration']['options']['url']);
        }
        return new Response(json_encode(array("success" => json_encode($sources))),
                200, array('Content-Type' => 'application/json'));
    }

}
