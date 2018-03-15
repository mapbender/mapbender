<?php
namespace Mapbender\WmtsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * WmtsLoader
 *
 * @author Karim Malhas
 * @author Paul Schmidt
 */
class WmtsLoader extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.wmts.wmtsloader.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.wmts.wmtsloader.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("mb.wmts.wmtsloader.wmts", "mb.wmts.wmtsloader.loader");
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
        return 'mapbender.mbWmtsloader';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        $files = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                'mapbender.element.wmtsloader.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.distpatcher.js'),
            'css' => array('@MapbenderWmtsBundle/Resources/public/sass/element/wmtsloader.scss'),
            'trans' => array('MapbenderWmtsBundle:Element:wmtsloader.json.twig'));
        return $files;
    }
    
     /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        $wmts_url = $this->container->get('request')->get('wmts_url');
        if ($wmts_url) {
            $all = $this->container->get('request')->query->all();
            foreach ($all as $key => $value) {
                if(strtolower($key) === "version" && stripos($wmts_url, "version") === false){
                    $wmts_url .= "&version=" . $value;
                } else if(strtolower($key) === "request" && stripos($wmts_url, "request") === false){
                    $wmts_url .= "&request=" . $value;
                } else if(strtolower($key) === "service" && stripos($wmts_url, "service") === false){
                    $wmts_url .= "&service=" . $value;
                }
            }
            $configuration['wmts_url'] = urldecode($wmts_url);
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
        return 'Mapbender\WmtsBundle\Element\Type\WmtsLoaderAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderWmtsBundle:ElementAdmin:wmtsloader.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderWmtsBundle:Element:wmtsloader.html.twig',
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
        return $this->container->get('http_kernel')->forward(
                'OwsProxy3CoreBundle:OwsProxy:entryPoint',
                array(
                'content' => $data
                ),array(
                'url' => urlencode($signedUrl)
                )
        );
        $path = array(
            '_controller' => 'OwsProxy3CoreBundle:OwsProxy:entryPoint',
            'url' => urlencode($signedUrl)
        );
        $subRequest = $this->container->get('request')->duplicate(array(), null, $path);
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
