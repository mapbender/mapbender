<?php

namespace Mapbender\CoreBundle\Element;

use FOM\UserBundle\Entity\User;
use JMS\Serializer\SerializerBuilder;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\PrintBundle\Element\Token\SignerToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
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
    static public function listAssets()
    {
        return array('js'    => array('vendor/jquery.dataTables.min.js',
                                      'mapbender.element.printClient.js',
                                      '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                                      '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
                     'css'   => array('@MapbenderCoreBundle/Resources/public/sass/element/printclient.scss'),
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
                array(
                    'template' => "a4_landscape_offical",
                    "label" => "A4 Landscape offical",
                    "format" => "a4"),
                array(
                    'template' => "a2_landscape_offical",
                    "label" => "A2 Landscape offical",
                    "format" => "a2")
            ),
            "scales" => array(500, 1000, 5000, 10000, 25000),
            "quality_levels" => array(array('dpi' => "72", 'label' => "Draft (72dpi)"),
                array('dpi' => "288", 'label' => "Document (288dpi)")),
            "rotatable" => true,
            'renderingMode' => 'direct',
            "optional_fields" => array(
                            "title" => array("label" => 'Title', "options" => array("required" => false)),
                            "comment1" => array("label" => 'Comment 1', "options" => array("required" => false)),
                            "comment2" => array("label" => 'Comment 2', "options" => array("required" => false))
                            ),
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

        $user                       = $this->container->get('mapbender.print.queue_manager')->getCurrentUser();
        $config["displayAllQueues"] = $user ? $user->isAdmin() : false;
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
        $configuration = $this->getConfiguration();

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
                
                if (isset($data['replace_pattern'])){
                    foreach ($data['replace_pattern'] as $idx => $value) {
                        $data['replace_pattern'][$idx] = json_decode($value, true);
                    }
                }
                
                if (isset($data['extent_feature'])){         
                        $data['extent_feature'] = json_decode($data['extent_feature'], true);                  
                }

                $user = $this->container->get('mapbender.print.queue_manager')->getCurrentUser();
                $data['renderMode']       = $configuration['renderMode'];
                $data['userId']           = $user ? $user->getId() : null;

                // Forward to Printer Service URL using OWSProxy
                return $this->forwardRequest($request, $data, $this->container->get('router')->generate('mapbender_print_print_service',array(),true));


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

            default: // redirect requests
                $request = $this->container->get('request');
                return $this->forwardRequest($request,
                    array('userId'  => $this->getCurrentUserId(),
                          'element' => $configuration,
                          'request' => $request->request->all()),
                    $this->container->get('router')->generate('mapbender_print_print_'.$action, array(), true)
                );
        }
    }

    /**
     * Get current user
     *
     * if $user == 'anon.', then user = null
     *
     * @return int
     */
    public function getCurrentUserId()
    {
        $token = $this->container->get('security.context')->getToken();
        $user  = $token ? $token->getUser() : null;
        return $user && $user instanceof User ? $user->getId() : null;
    }

    /**
     * Generate sub request
     *
     * @param Request $request
     * @param array $data Data to send
     * @param string $url Request URL
     * @throws \Exception
     * @return Response
     */
    protected function forwardRequest(Request $request, $data, $url)
    {
        return $this->container->get('http_kernel')->handle($request->duplicate(array(),
                null,
                array('_controller' => 'OwsProxy3CoreBundle:OwsProxy:genericProxy',
                      'url'         => $url,
                      'content'     => $this->container->get('signer')->dump(new SignerToken($data)))
            ),
            HttpKernelInterface::SUB_REQUEST
        );
    }

}
