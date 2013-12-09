<?php
namespace Mapbender\WmsBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;

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
        return "WmsLoader";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Opens a dialog in which a WMS can be loaded via the getCapabilities-Request";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
    {
        return array("wms", "loader");
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
    public function getAssets()
    {
        $files = array(
            'js' => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                'mapbender.element.wmsloader.js'),
            'css' => array(),
            'trans' => array('MapbenderWmsBundle:Element:wmsloader.json.twig'));//@MapbenderCoreBundle/Resources/view/Element/wmsloader.json.twig'));
        $config = $this->getConfiguration();
        if (isset($config['useDeclarative']) && $config['useDeclarative'] === true) {
            $files['js'][] = "@MapbenderCoreBundle/Resources/public/mapbender.distpatcher.js";
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
        return $this->container->get('http_kernel')->forward(
                'OwsProxy3CoreBundle:OwsProxy:entryPoint',
                array(
                'content' => $data
                ),array(
                'url' => urlencode($signedUrl)
                )
        );
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
