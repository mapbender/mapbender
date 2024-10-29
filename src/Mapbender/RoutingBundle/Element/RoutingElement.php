<?php

namespace Mapbender\RoutingBundle\Element;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Doctrine\Common\Collections\Collection;
use Exception;
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

/**
 * Class RoutingElement
 * @package Mapbender\RoutingBundle\Element
 * @author Christian Kuntzsch <christian.kuntzsch@wheregroup.com>
 * @author Robert Klemm <robert.klemm@wheregroup.com>
 */
class RoutingElement extends AbstractElementService implements ConfigMigrationInterface, ElementHttpHandlerInterface
{
    protected SearchHandler $searchHandler;

    protected RoutingHandler $routingHandler;

    public function __construct(SearchHandler $searchHandler, RoutingHandler $routingHandler) {
        $this->searchHandler = $searchHandler;
        $this->routingHandler = $routingHandler;
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
        return [
            'js' => [
                '@MapbenderRoutingBundle/Resources/public/mapbender.element.routingelement.js',
                # "/components/jquery-context-menu/src/jquery.contextMenu.js",
                # "/components/jquery-context-menu/src/jquery.ui.position.js",
                # "/bundles/mapbendercore/proj4js/proj4js-compressed.js",
                # "/components/jquery-ui/ui/widgets/autocomplete.js",
            ],
            'css' => [
                '@MapbenderRoutingBundle/Resources/public/sass/element/jquery-ui.css',
                '@MapbenderRoutingBundle/Resources/public/sass//mapbender.element.routing.scss',
                # '@MapbenderRoutingBundle/Resources/public/sass/element/routing.scss',
                # '/components/jquery-context-menu/src/jquery.contextMenu.css'),
            ],
            'trans' => [
                'mb.routing.*',
                //'@MapbenderRoutingBundle/Resources/views/Element/routingelement.json.twig',
            ],
        ];
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
            'allowContextMenu' => false,
            'useSearch' => false,
            'useReverseGeocoding' => false,
            'buffer' => 0,
            'infoText' => '{start} → {destination} </br> {length} will take {time}',
            'dateTimeFormat' => 'ms',
            'routingDriver' => null,
            'routingStyles' => [
                'lineColor' => '#4286F4',
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
            case 'revGeocode':
                $response = new ReverseSearchHandler();
                break;
            default:
                throw new NotFoundHttpException('No such action.');
        }
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

    public static function getFormTemplate(): string
    {
        return '@MapbenderRouting/ElementAdmin/routingelementadmin.html.twig';
    }

    public function getWidgetName( Element $element ): string
    {
        return 'mapbender.mbRoutingElement';
    }

    public static function getType(): ?string
    {
        return 'Mapbender\RoutingBundle\Element\Type\RoutingElementAdminType';
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


    /**
     * Modify Frontend-Configuration
     * @return array|null
     */
    public function getClientConfiguration(Element $element) : Array|null
    {
        $configuration = $element->getConfiguration();

        if (!isset($configuration['routingDriver'])) {
            new Exception('Routing Driver is not set');
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
