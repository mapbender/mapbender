<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\PrintBundle\Component\OdgParser;

/**
 * 
 */
class PrintClient extends Element
{
    public static $merge_configurations = false;

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "mb.core.printclient.class.title";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "mb.core.printclient.class.description";
    }

    /**
     * @inheritdoc
     */
    static public function getClassTags()
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
            "autoOpen" => false,
            "templates" => array(
                array(
                    'template' => "a4portrait",
                    "label" => "A4 Portrait",
                    "format" => "a4")
                ,
                array(
                    'template' => "a4landscape",
                    "label" => "A4 Landscape",
                    "format" => "a4")
                ,
                array(
                    'template' => "a3portrait",
                    "label" => "A3 Portrait",
                    "format" => "a3")
                ,
                array(
                    'template' => "a3landscape",
                    "label" => "A3 Landscape",
                    "format" => "a3")
                ,
                /*array(
                    'template' => "a2_landscape_offical",
                    "label" => "A2 Landscape offical",
                    "format" => "a2")
                ,
                array(
                    'template' => "a4_landscape_offical",
                    "label" => "A4 Landscape offical",
                    "format" => "a4")*/
            ),
            "scales" => array(500, 1000, 5000, 10000, 25000),
            "quality_levels" => array(array('dpi' => "72", 'label' => "Draft (72dpi)"),
                array('dpi' => "288", 'label' => "Draft (288dpi)")),
            "rotatable" => true,
            "optional_fields" => null,
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
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:printclient.html.twig',
                    array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()
        ));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch ($action) {
            case 'direct':

                $request = $this->container->get('request');
                $data = $request->request->all();

                foreach ($request->request->keys() as $key) {
                    $request->request->remove($key);
                }
                // keys, remove
                foreach ($data['layers'] as $idx => $layer) {
                    $data['layers'][$idx] = json_decode($layer, true);
                }
                
                if (isset($data['overview'])){
                    foreach ($data['overview'] as $idx => $layer) {
                        $data['overview'][$idx] = json_decode($layer, true);
                    }
                }
                
                if (isset($data['features'])){
                    foreach ($data['features'] as $idx => $value) {
                        $data['features'][$idx] = json_decode($value, true);
                    }
                }
                
                if (isset($data['extent_feature'])){         
                        $data['extent_feature'] = json_decode($data['extent_feature'], true);                  
                }
                    
                $content = json_encode($data);

                // Forward to Printer Service URL using OWSProxy
                $url = $this->container->get('router')->generate('mapbender_print_print_service',
                    array(), true);

                return $this->container->get('http_kernel')->forward(
                        'OwsProxy3CoreBundle:OwsProxy:genericProxy',
                        array(
                        'url' => $url,
                        'content' => $content
                        )
                );

            case 'queued':

            case 'template':
                $response = new Response();
                $response->headers->set('Content-Type', 'application/json');
                $request = $this->container->get('request');
                $data = json_decode($request->getContent(), true);
                $container = $this->container;
                $odgParser = new OdgParser($container);
                $size = $odgParser->getMapSize($data['template']);
                $response->setContent($size->getContent());
                return $response;
        }
    }

}
