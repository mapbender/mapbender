<?php

namespace Mapbender\CoreBundle\Element;

use Doctrine\Persistence\ConnectionRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Element\Type\SearchRouterFormType;
use Mapbender\CoreBundle\Entity\Element;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * SearchRouter element.
 *
 * @author Christian Wygoda
 */
class SearchRouter extends AbstractElementService implements ConfigMigrationInterface, ElementHttpHandlerInterface
{
    /** @var ConnectionRegistry */
    protected $connectionRegistry;
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var LoggerInterface|null */
    protected $logger;
    protected CsrfTokenManagerInterface $csrfTokenManager;

    public function __construct(ConnectionRegistry        $connectionRegistry,
                                FormFactoryInterface      $formFactory,
                                CsrfTokenManagerInterface $csrfTokenManager,
                                LoggerInterface           $logger = null)
    {
        $this->connectionRegistry = $connectionRegistry;
        $this->formFactory = $formFactory;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->logger = $logger;
    }

    public static function getClassTitle()
    {
        return 'mb.core.searchrouter.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.searchrouter.class.description';
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\SearchRouterAdminType';
    }

    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:search_router.html.twig';
    }

    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbSearchRouter';
    }

    public static function getDefaultConfiguration()
    {
        return array(
            "width" => 700,
            "height" => 500,
            "routes" => array(),
        );
    }

    /**
     * @return array
     */
    protected function getDefaultRouteConfiguration()
    {
        return array(
            "title" => "mb.core.searchrouter.tag.search",
            "class" => 'Mapbender\CoreBundle\Component\SQLSearchEngine',
            "class_options" => array(
                "connection" => 'default',
                "relation" => "geometries",
                "attributes" => array("*"),
                "geometry_attribute" => "geom",
            ),
            "results" => array(
                "view" => "table",
                "count" => "true",
                "headers" => array(),
                "callback" => array(
                    "event" => "click",
                    "options" => array(
                        "buffer" => 1000,
                        "minScale" => null,
                        "maxScale" => null
                    ),
                ),
                "styleMap" => $this->getDefaultStyleMapOptions(),
            ),
        );
    }

    protected function getDefaultStyleMapOptions()
    {
        return array(
            "default" => array(
                "strokeColor" => "#dd0000",
                "fillColor" => "#ee2222",
                "fillOpacity" => 0.4,
                "strokeOpacity" => 0.8,
            ),
            "select" => array(
                "strokeColor" => "#dd0000",
                "fillColor" => "#ee2222",
                "fillOpacity" => 0.8,
                "strokeOpacity" => 1.0,
            ),
            "temporary" => array(
                "strokeColor" => "#ee8822",
                "fillColor" => "#ee8800",
                "fillOpacity" => 0.8,
                "strokeOpacity" => 1.0,
            ),
        );
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:search_router.html.twig');
        $view->attributes['class'] = 'mb-element-searchrouter';
        $view->attributes['data-title'] = $element->getTitle();

        $view->variables = array(
            'route_select_form' => $this->getRouteSelectForm($element)->createView(),
            'search_forms' => $this->getFormViews($element),
        );
        return $view;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.searchRouter.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/search_router.scss',
            ),
            'trans' => array(
                'mb.core.searchrouter.result_counter',
                'mb.core.searchrouter.no_results',
            ),
        );
    }

    public function getHttpHandler(Element $element)
    {
        return $this;
    }

    public function handleRequest(Element $element, Request $request)
    {
        $actionParts = explode('/', $request->attributes->get('action'));
        if (count($actionParts) !== 2) {
            throw new NotFoundHttpException();
        }
        $categoryId = $actionParts[0];
        $action = $actionParts[1];

        if ('csrf' === $action) {
            $generatedToken = $this->csrfTokenManager->getToken(SearchRouterFormType::class);
            return new Response($generatedToken->getValue());
        }

        $routeConfigs = \array_values($element->getConfiguration()['routes']);
        if (empty($routeConfigs[$categoryId])) {
            throw new NotFoundHttpException();
        }
        $categoryConf = array_replace_recursive($this->getDefaultRouteConfiguration(), $routeConfigs[$categoryId]);
        $engineClassName = $categoryConf['class'];
        $engine = new $engineClassName($this->buildEngineContainer($engineClassName));
        $data = json_decode($request->getContent(), true);

        if (in_array($action, ['autocomplete', 'search'])) {
            $token = isset($data['properties']['_token']) ? new CsrfToken(SearchRouterFormType::class, $data['properties']['_token']) : null;
            $isValid = $token !== null && $this->csrfTokenManager->isTokenValid($token);

            if (!$isValid) {
                return new Response('Invalid CSRF token.', Response::HTTP_BAD_REQUEST);
            }
        }

        if ('autocomplete' === $action) {
            $results = $engine->autocomplete(
                $categoryConf,
                $data['key'],
                $data['value'],
                $data['properties'],
                $data['srs'],
                $data['extent']
            );
            return new JsonResponse(array_replace($data, array(
                'results' => $results,
            )));
        }

        if ('search' === $action) {
            $form = $this->getForm($categoryConf, $categoryId);
            $form->submit($data['properties']);
            $query = array(
                'form' => $form->getData(),
            );
            $features = $engine->search($categoryConf, $query, $data['srs'], $data['extent']);
            return new JsonResponse(array(
                'type' => 'FeatureCollection',
                'features' => $features,
            ));
        }

        throw new NotFoundHttpException();
    }

    /**
     * Create form for selecting search route (= search form) to display.
     *
     * @param Element $element
     * @return FormInterface Search route select form
     */
    protected function getRouteSelectForm(Element $element)
    {
        $defaultTitle = $this->getDefaultRouteConfiguration()['title'];
        $routeConfigs = $element->getConfiguration()['routes'];
        $choices = array();
        foreach (\array_values($routeConfigs) as $i => $routeConfig) {
            $title = (!empty($routeConfig['title'])) ? $routeConfig['title'] : $defaultTitle;
            $choices[$title] = $i;
        }
        return $this->formFactory->createNamed('search_routes_route', 'Symfony\Component\Form\Extension\Core\Type\ChoiceType', null, array(
            'choices' => $choices,
        ));
    }

    /**
     * @param $categoryConfig
     * @param $categoryId
     * @return FormInterface
     */
    protected function getForm($categoryConfig, $categoryId)
    {
        return $this->formFactory->createNamed($categoryId, 'Mapbender\CoreBundle\Element\Type\SearchRouterFormType', null, array(
            'fields' => $categoryConfig,
        ));
    }

    /**
     * @param Element $element
     * @return FormView[]
     */
    protected function getFormViews(Element $element)
    {
        $formViews = array();
        $routeDefaults = $this->getDefaultRouteConfiguration();
        $routeConfigs = \array_values($element->getConfiguration()['routes']);
        foreach ($routeConfigs as $i => $conf) {
            $conf = array_replace_recursive($routeDefaults, $conf);
            $formViews[] = $this->getForm($conf, $i)->createView();
        }
        return $formViews;
    }

    public static function updateEntityConfig(Element $entity)
    {
        $configuration = $entity->getConfiguration();
        foreach ($configuration['routes'] as $routeKey => $routeValue) {
            if (!empty($routeValue['configuration']) && \is_array($routeValue['configuration'])) {
                $routeValue = $routeValue['configuration'] + $routeValue;
            }
            unset($routeValue['configuration']);
            $configuration['routes'][$routeKey] = $routeValue;
        }
        $entity->setConfiguration($configuration);
    }

    public function getClientConfiguration(Element $element)
    {
        $routeDefaults = $this->getDefaultRouteConfiguration();
        $config = $element->getConfiguration();
        foreach ($config['routes'] as $key => $routeConfig) {
            $config['routes'][$key] = array_replace_recursive($routeDefaults, $routeConfig);
        }
        $config['routes'] = \array_values($config['routes']);
        return $config;
    }

    protected function buildEngineContainer($engineClassName)
    {
        $container = new Container();
        switch ($engineClassName) {
            default:
            case 'Mapbender\CoreBundle\Component\SQLSearchEngine':
                $container->set('doctrine', $this->connectionRegistry);
                $container->set('logger', $this->logger ?: new NullLogger());
                return $container;
        }
    }
}
