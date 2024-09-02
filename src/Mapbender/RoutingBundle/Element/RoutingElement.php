<?php

namespace Mapbender\RoutingBundle\Element;

use Doctrine\DBAL\Connection;
use Mapbender\CoreBundle\Component\Element;
use Mapbender\RoutingBundle\Component\RequestHandler;
use Mapbender\RoutingBundle\Component\ReverseSearchHandler;
use Mapbender\RoutingBundle\Component\RoutingHandler;
use RuntimeException;
use Mapbender\RoutingBundle\Component\SearchHandler;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class RoutingElement
 * @package Mapbender\RoutingBundle\Element
 * @author Christian Kuntzsch <christian.kuntzsch@wheregroup.com>
 * @author Robert Klemm <robert.klemm@wheregroup.com>
 */
class RoutingElement extends Element
{
    /**
     * @var Connection|object
     */
    protected $connection;

    /**
     * @return string
     */
    public static function getClassTitle()
    {
        return "mb.routing.backend.title";
    }

    /**
     * @return string
     */
    public static function getClassDescription()
    {
        return "mb.routing.backend.description";
    }

    /**
     * @return array
     */
    public static function getClassTags()
    {
        return array();
    }

    /**
     * @return array
     */
    static public function listAssets()
    {
        return array(
            'js' => array(
                '@MapbenderRoutingBundle/Resources/public/mapbender.element.routingelement.js',
                "/components/jquery-context-menu/src/jquery.contextMenu.js",
                "/components/jquery-context-menu/src/jquery.ui.position.js",
                "/bundles/mapbendercore/proj4js/proj4js-compressed.js",
                "/components/jquery-ui/ui/widgets/autocomplete.js"
                ),
            'css' => array(
                '@MapbenderRoutingBundle/Resources/public/sass/element/routing.scss',
                '@MapbenderRoutingBundle/Resources/public/sass/element/jquery-ui.css',
                '/components/jquery-context-menu/src/jquery.contextMenu.css'),
            'trans' => array('MapbenderRoutingBundle:Element:routingelement.json.twig')
        );
    }

    /**
     * @return string
     */
    public function getWidgetName()
    {
        return 'mapbender.mbRoutingElement';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\RoutingBundle\Element\Type\RoutingElementAdminType';
    }

    /**
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Routing',
            'target' => null,
            'autoSubmit' => false,
            'addIntermediatePoints' => false,
            'disableContextMenu' => false,
            'addSearch' => false,
            'addReverseGeocoding' => false,
            'advanced' => false,
            'routingDriver' => null,
            'buffer' => 0,
            'color' => '#4286F4',
            'width' => 3,
            'opacity' => 1,
            'styleMap' => array(
                'start' => array(),
                'intermediate' => array(),
                'destination' => array(),
                'route' => array(
                    'strokeWidth' => 3,
                    'strokeColor' => '#4286F4',
                    'strokeOpacity' => 0.8,
                    'fillOpacity' => 0.8
                )
            ),
            'infoText' => '{start} → {destination} </br> {length} will take {time}',
            'dateTimeFormat' => 'ms',
            'backendConfig' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderRoutingBundle:ElementAdmin:routingelementadmin.html.twig';
    }


    /**
     * Get Configuration from Backend Admintype  or yml-syntax
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @return array
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();

        if (!isset($configuration['routingDriver'])) {
            new \Exception('Routing Driver is not set');
        }

        if (isset($configuration['search'])) {
            $configuration = array_merge($configuration, $configuration['search']);
        }

        return $configuration;
    }




    /**
     * @return string
     */
    public function render()
    {
        $configuration = $this->getConfiguration();
        $transportationModes = $this->getTransportationModes();

        return $this->container->get('templating')->render(
            'MapbenderRoutingBundle:Element:routingelement.html.twig',
            array(
                'id' => $this->getId(),
                'title' => $this->getTitle(),
                'transportationModes' => $transportationModes,
                'addIntermediatePoints' => $configuration['addIntermediatePoints'],
                'autoSubmit' => $configuration['autoSubmit']
            )
        );
    }

    /**
     * @param $action
     * @throws $action if fail
     * @return JsonResponse|Response
     */
    public function httpAction($action)
    {
        /**
         * @var $hander RequestHandler
         */
        switch($action) {
            case 'getRoute':
                $handler = new RoutingHandler();
                break;
            case 'search':
                $handler = new SearchHandler();
                break;
            case 'revGeocode':
                $handler = new ReverseSearchHandler();
                break;
            default:
                throw new NotFoundHttpException('No such action');
        }

        return $handler->getAction($this->getConfiguration(),$this->container);

    }


    /**
     * @return array
     */
    protected function getTransportationModes()
    {
        $defaultTransportationModes = array(
            0 => 'car'
        );

        $configuration = $this->getConfiguration();
        $routingDriver = $configuration['routingDriver'];
        $backendConfig = $configuration['backendConfig'];

        // Check TransportationsMode from backend
        foreach($backendConfig as $key=>$value) {
            If ($key === $routingDriver) {
                $backendConfig=$value;
            }
        }

        // check ist set TransportationMode => else Default-Mode
        if(isset($backendConfig['transportationMode'])){
            $transportationModes=$backendConfig['transportationMode'];
        }else {
            $transportationModes= $defaultTransportationModes;
        }

        return $transportationModes;
    }



    /**
     * Modify Frontend-Configuration
     * @return array|null
     */
    public function getPublicConfiguration()
    {
        $pubConfig = $this->entity->getConfiguration();

        // delete Backendconfig
        unset($pubConfig['backendConfig']);

        return $pubConfig + array(
                // NOTE: intl extension locale is runtime-controlled by Symfony to reflect framework configuration
                'locale' => \locale_get_default(),
            );
    }
}
