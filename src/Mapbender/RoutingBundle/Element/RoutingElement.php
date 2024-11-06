<?php

namespace Mapbender\RoutingBundle\Element;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use Exception;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\RoutingBundle\Component\RoutingHandler;
use Mapbender\RoutingBundle\Component\SearchHandler;
use Mapbender\RoutingBundle\Component\ReverseGeocodingHandler;

/**
 * Class RoutingElement
 * @package Mapbender\RoutingBundle\Element
 * @author Christian Kuntzsch <christian.kuntzsch@wheregroup.com>
 * @author Robert Klemm <robert.klemm@wheregroup.com>
 */
class RoutingElement extends AbstractElementService
{
    protected RoutingHandler $routingHandler;

    protected SearchHandler $searchHandler;

    protected ReverseGeocodingHandler $reverseGeocodingHandler;

    public function __construct(RoutingHandler $routingHandler, SearchHandler $searchHandler, ReverseGeocodingHandler $reverseGeocodingHandler) {
        $this->routingHandler = $routingHandler;
        $this->searchHandler = $searchHandler;
        $this->reverseGeocodingHandler = $reverseGeocodingHandler;
    }

    /**
     * @return string
     */
    public static function getClassTitle() : string
    {
        return 'mb.routing.backend.title';
    }

    /**
     * @return string
     */
    public static function getClassDescription() : string
    {
        return 'mb.routing.backend.description';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return [
            'js' => [
                '@MapbenderRoutingBundle/Resources/public/mapbender.element.routingelement.js',
            ],
            'css' => [
                '@MapbenderRoutingBundle/Resources/public/sass/mapbender.element.routing.scss',
                '@MapbenderRoutingBundle/Resources/public/css/jquery-ui.css',
            ],
            'trans' => [
                'mb.routing.*',
            ],
        ];
    }

    public function getWidgetName( Element $element ): string
    {
        return 'mapbender.mbRoutingElement';
    }

    /**
     * @return array
     */
    public static function getDefaultConfiguration()
    {
        return [
            'title' => 'Routing',
            'advancedSettings' => false,
            'autoSubmit' => false,
            'allowIntermediatePoints' => false,
            'useSearch' => false,
            'useReverseGeocoding' => false,
            'buffer' => 0,
            'infoText' => '{start} → {destination} </br> {length} will take {time}',
            'dateTimeFormat' => 'ms',
            'routingDriver' => null,
            'routingStyles' => [
                'lineColor' => 'rgba(66, 134, 244, 1)',
                'lineWidth' => 3,
                'lineOpacity' => 1,
                'startIcon' => [
                    'imagePath' => '/bundles/mapbenderrouting/image/start.png',
                ],
                'intermediateIcon' => [],
                'destinationIcon' => [
                    'imagePath' => '/bundles/mapbenderrouting/image/destination.png',
                ],
            ],
        ];
    }

    public static function getFormTemplate(): string
    {
        return '@MapbenderRouting/ElementAdmin/routingelementadmin.html.twig';
    }

    public static function getType(): ?string
    {
        return 'Mapbender\RoutingBundle\Element\Type\RoutingElementAdminType';
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration();
        $view = new TemplateView('@MapbenderRouting/Element/routingelement.html.twig');
        $view->attributes['class'] = 'mb-element-routing';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables = [
            'id' => $element->getId(),
            'title' => $element->getTitle(),
            'transportationModes' => $this->getTransportationModes($element),
            'allowIntermediatePoints' => $config['allowIntermediatePoints'],
        ];
        return $view;
    }

    public function getHttpHandler(Element $element)
    {
        return $this;
    }

    /**
     * @throws Exception
     */
    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        $configuration = $element->getConfiguration();

        switch ($action) {
            case 'getRoute':
                return $this->routingHandler->calculateRoute($request->request->all(), $configuration);
            case 'search':
                return $this->searchHandler->search($request->query->all(), $configuration);
            case 'reverseGeocoding':
                return $this->reverseGeocodingHandler->doReverseGeocoding($request->query->all(), $configuration);
            default:
                throw new NotFoundHttpException('No such action.');
        }
    }

    protected function getTransportationModes(Element $element): array
    {
        $transportationModes = [
            0 => 'car',
        ];
        $configuration = $element->getConfiguration();
        $routingDriver = $configuration['routingDriver'];
        $routingConfigs = $configuration['routingConfig'];

        // Check TransportationsMode from backend
        foreach ($routingConfigs as $name => $config) {
            if ($name === $routingDriver) {
                $routingConfig = $config;
            }
        }

        // check ist set TransportationMode => else Default-Mode
        if (isset($routingConfig['transportationMode'])){
            $transportationModes = $routingConfig['transportationMode'];
        }

        return $transportationModes;
    }
}
