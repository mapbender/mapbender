<?php

namespace Mapbender\RoutingBundle\Element;

use Doctrine\Common\Collections\Collection;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\ElementServiceInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\Source\UrlProcessor;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\RoutingBundle\Component\RoutingHandler;
use Mapbender\RoutingBundle\Component\ReverseSearchHandler;
use Mapbender\RoutingBundle\Component\SearchHandler;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class RoutingElement
 * @package Mapbender\RoutingBundle\Element
 * @author Christian Kuntzsch <christian.kuntzsch@wheregroup.com>
 * @author Robert Klemm <robert.klemm@wheregroup.com>
 */
class RoutingElement extends AbstractElementService implements ConfigMigrationInterface, ElementHttpHandlerInterface
{
     /** @var UrlGeneratorInterface */
    protected $urlGenerator;
    public function __construct(UrlGeneratorInterface $urlGenerator){
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @return string
     */
    public static function getClassTitle() : string
    {
        return "mb.routing.backend.title";
    }

    /**
     * @return string
     */
    public static function getClassDescription() : string
    {
        return "mb.routing.backend.description";
    }

    public static function updateEntityConfig(Element $element) : void
    {
        $values = $element->getConfiguration();
        if ($values && !empty($values['scales'])) {
            // Force all 'scales' values to integer
            $values['scales'] = array_map('intval', $values['scales']);
            // Remove (invalid) 0 / null / empty 'scales' values
            $values['scales'] = array_filter($values['scales']);
            $element->setConfiguration($values);
        }
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
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
            'trans' => array(
                'MapbenderRoutingBundle:Element:routingelement.json.twig')
        );
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

    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        $configuration = $element->getConfiguration();
        $response = new Response();
        switch ($action) {
            case 'getRoute':
                $response = new RoutingHandler();
                break;
            case 'search':
                $response = new SearchHandler();
                break;
            case 'revGeocode':
                $response = new ReverseSearchHandler();
                break;

            default:
                //$response = $this->pluginRegistry->handleHttpRequest($request, $element);
        }
        if ($response) {
            return $response;
        } else {
            throw new NotFoundHttpException();
        }
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration();
        $template = '@Mapbender/RoutingBundle/Element/routingelement.html.twig';

        $view = new TemplateView($template);
        $view->attributes['class'] = 'mb-element-routingelement';
        $view->attributes['data-title'] = $element->getTitle();

        $submitUrl = $this->urlGenerator->generate('mapbender_core_application_element', array(
            'slug' => $element->getApplication()->getSlug(),
            'id' => $element->getId(),
        ));
        $view->variables = array(
            'submitUrl' => $submitUrl,
            'id' => $element->getId(),
            'title' => $element->getTitle(),
            'transportationModes' => $this->getTransportationModes($element),
            'addIntermediatePoints' => $config['addIntermediatePoints'],
            'autoSubmit' => $config['autoSubmit'],
            'configuration' => $config + array(
                    'required_fields_first' => false,
                    'type' => 'dialog',
                ),
        );
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderRouting/ElementAdmin/routingelementadmin.html.twig';
    }

    /**
     * @return string
     */
    public function getWidgetName( Element $element )
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
    protected function getTransportationModes(Element $element)
    {
        $defaultTransportationModes = array(
            0 => 'car'
        );

        $configuration = $element->getConfiguration();
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
    public function getClientConfiguration(Element $element) : Array|null
    {
        $configuration = $element->getConfiguration();

        if (!isset($configuration['routingDriver'])) {
            new \Exception('Routing Driver is not set');
        }

        if (isset($configuration['search'])) {
            $configuration = array_merge($configuration, $configuration['search']);
        }

        return $configuration + array(
            // NOTE: intl extension locale is runtime-controlled by Symfony to reflect framework configuration
            'locale' => \locale_get_default(),
        );
    }
}
