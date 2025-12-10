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
 */
class Routing extends AbstractElementService
{
    protected RoutingHandler $routingHandler;

    protected SearchHandler $searchHandler;

    protected ReverseGeocodingHandler $reverseGeocodingHandler;

    public function __construct(RoutingHandler $routingHandler, SearchHandler $searchHandler, ReverseGeocodingHandler $reverseGeocodingHandler) {
        $this->routingHandler = $routingHandler;
        $this->searchHandler = $searchHandler;
        $this->reverseGeocodingHandler = $reverseGeocodingHandler;
    }

    public static function getClassTitle() : string
    {
        return 'mb.routing.backend.title';
    }

    public static function getClassDescription() : string
    {
        return 'mb.routing.backend.description';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderRoutingBundle/Resources/public/MbRouting.js',
            ],
            'css' => [
                '@MapbenderRoutingBundle/Resources/public/sass/routing-icons.scss',
                '@MapbenderRoutingBundle/Resources/public/sass/mapbender.element.routing.scss',
            ],
            'trans' => [
                'mb.routing.*',
            ],
        ];
    }

    public function getWidgetName( Element $element ): string
    {
        return 'MbRouting';
    }

    public static function getDefaultConfiguration(): array
    {
        return [
            'title' => 'Routing',
            'advancedSettings' => false,
            'autoSubmit' => false,
            'allowIntermediatePoints' => false,
            'allowExport' => false,
            'useSearch' => false,
            'useReverseGeocoding' => false,
            'buffer' => 100,
            'infoText' => '{start} → {destination} </br> {length} / {time}',
            'routingDriver' => null,
            'routingStyles' => [
                'lineColor' => 'rgba(66, 134, 244, 1)',
                'lineWidth' => 3,
                'lineOpacity' => 1,
                'startIcon' => [
                    'imagePath' => '/bundles/mapbenderrouting/image/start.png',
                ],
                'intermediateIcon' => [
                    'imagePath' => '/bundles/mapbenderrouting/image/intermediate.png',
                ],
                'destinationIcon' => [
                    'imagePath' => '/bundles/mapbenderrouting/image/destination.png',
                ],
            ],
            'routingConfig' => [
                'osrm' => [
                    'url' => 'https://routing.openstreetmap.de/routed-%profile',
                    'attribution' => 'Daten © <a href="https://www.openstreetmap.org/copyright" class="link-primary">OpenStreetMap</a>-Mitwirkende
  (<a href="https://opendatacommons.org/licenses/odbl/index.html" class="link-primary">ODbL</a>), <a
  href="https://creativecommons.org/licenses/by-sa/2.0/" class="link-primary">CC-BY-SA</a>, <a
  href="https://openstreetmap.org/fixthemap" class="link-primary">mitmachen/Fehler melden</a>',
                ],
            ],
            'searchConfig' => [
                'solr' => [
                    'url' => 'https://osm-photon-search.wheregroup.com/search/api?limit=20&lat=50.7163&lon=7.1366&osm_tag=!railway&osm_tag=!highway:elevator&osm_tag=!tourism&osm_tag=!amenity',
                    'query_key' => 'q',
                    'query_format' => '%s',
                    'collection_path' => 'features',
                    'label_attribute' => '${properties.name} ${properties.street} ${properties.housenumber} ${properties.city} ${properties.country}',
                    'geom_attribute' => 'geometry',
                    'geom_format' => 'GeoJSON',
                    'geom_proj' => 'EPSG:4326',
                ],
            ],
            'element_icon' => self::getDefaultIcon(),
        ];
    }
    public static function getDefaultIcon()
    {
        return 'iconRouting';
    }

    public static function getFormTemplate(): string
    {
        return '@MapbenderRouting/ElementAdmin/routing.html.twig';
    }

    public static function getType(): ?string
    {
        return 'Mapbender\RoutingBundle\Element\Type\RoutingAdminType';
    }

    public function getView(Element $element): TemplateView
    {
        $config = $element->getConfiguration();
        $view = new TemplateView('@MapbenderRouting/Element/routing.html.twig');
        $view->attributes['class'] = 'mb-element-routing';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables = [
            'id' => $element->getId(),
            'title' => $element->getTitle(),
            'transportationModes' => $this->getTransportationModes($element),
            'allowIntermediatePoints' => $config['allowIntermediatePoints'],
            'allowExport' => $config['allowExport'],
            'attribution' => $config['routingConfig']['osrm']['attribution'],
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
        $configuration['locale'] = $request->getLocale();

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
