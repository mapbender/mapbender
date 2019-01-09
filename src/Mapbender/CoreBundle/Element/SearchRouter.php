<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\SQLSearchEngine;
use Mapbender\CoreBundle\Element\Type\SearchRouterFormType;
use Mapbender\CoreBundle\Element\Type\SearchRouterSelectType;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * SearchRouter element.
 *
 * @author Christian Wygoda
 */
class SearchRouter extends Element
{
    const FEATURE_DEFAULT_COLOR   = "#33CCFF";
    const FEATURE_SELECT_COLOR    = "#ff0000";
    const FEATURE_OPACITY         = 0.8;
    const FEATURE_BUFFER          = 1000;
    const GEOM_FIELD_NAME         = "geom";
    const DEFAULT_SEARCH_ENGINE   = "Mapbender\\CoreBundle\\Component\\SQLSearchEngine";
    const DEFAULT_CONNECTION_NAME = "default";
    const DEFAULT_ROUTE_TITLE     = "mb.core.searchrouter.tag.search";

    public static function getClassTitle()
    {
        return 'mb.core.searchrouter.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.searchrouter.class.description';
    }

    public static function getClassTags()
    {
        return array(
            'mb.core.searchrouter.tag.search',
            'mb.core.searchrouter.tag.router',
        );
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip'       => "mb.core.searchrouter.class.title",
            'title'         => "mb.core.searchrouter.class.title",
            "target"        => null,
            // Alternative "timeout" option is deprecated
            'timeoutFactor' => 3,
            "width"         => 700,
            "height"        => 500,
            "routes"        => array(),
            // Alternative "dialog" is deprecated
            "asDialog"      => false,
        );
    }

    /** @var Form[] Element search forms */
    protected $forms;

    /**
     * Default route configuration
     *
     * @var array
     */
    protected static $defaultRouteConfiguration = array(
        "title"         => self::DEFAULT_ROUTE_TITLE,
        "class"         => self::DEFAULT_SEARCH_ENGINE,
        "class_options" => array(
            "connection"         => self::DEFAULT_CONNECTION_NAME,
            "relation"           => "geometries",
            "attributes"         => array("*"),
            "geometry_attribute" => self::GEOM_FIELD_NAME,
        ),
        "results"       => array(
            "view"     => "table",
            "count"    => "true",
            "headers"  => array(),
            "callback" => array(
                "event"   => "click",
                "options" => array(
                    "buffer"   => self::FEATURE_BUFFER,
                    "minScale" => null,
                    "maxScale" => null
                )
            )
        ),
        "styleMap"      => array(
            "default" => array(
                "strokeColor" => self::FEATURE_DEFAULT_COLOR,
                "fillColor"   => self::FEATURE_DEFAULT_COLOR,
                "fillOpacity" => self::FEATURE_OPACITY
            ),
            "select"  => array(
                "strokeColor" => self::FEATURE_SELECT_COLOR,
                "fillColor"   => self::FEATURE_SELECT_COLOR,
                "fillOpacity" => self::FEATURE_OPACITY
            ),
        )
    );

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js'    => array(
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
                'vendor/json2.js',
                'vendor/backbone.js',
                'mapbender.element.searchRouter.Feature.js',
                'mapbender.element.searchRouter.Search.js',
                'mapbender.element.searchRouter.js'),
            'css'   => array('@MapbenderCoreBundle/Resources/public/sass/element/search_router.scss'),
            'trans' => array('MapbenderCoreBundle:Element:search_router.json.twig'));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var SQLSearchEngine $engine */
        $response = new JsonResponse();
        //$response->headers->set('Content-Type', 'application/json');
        $request = $this->container->get('request');

        list($target, $action) = explode('/', $action);
        $conf = $this->getConfiguration();

        if (!array_key_exists($target, $conf['routes'])) {
            throw new NotFoundHttpException();
        }

        if ('autocomplete' === $action) {
            $data = json_decode($request->getContent());

            // Get search config
            $conf = $this->getConfiguration();
            if (!array_key_exists($target, $conf['routes'])) {
                throw new NotFoundHttpException();
            }
            $conf    = $conf['routes'][ $target ];
            $engine  = new $conf['class']($this->container);
            $results = $engine->autocomplete(
                $conf,
                $data->key,
                $data->value,
                $data->properties,
                $data->srs,
                $data->extent
            );

            $response->setData(array(
                'key'        => $data->key,
                'value'      => $data->value,
                'properties' => $data->properties,
                'results'    => $results
            ));
            return $response;
        }

        if ('search' === $action) {
            $this->getForms();
            $data = json_decode($request->getContent());
            $form = $this->forms[ $target ];
            $form->submit(get_object_vars($data->properties));
            $conf     = $conf['routes'][ $target ];
            $engine   = new $conf['class']($this->container);
            $query    = array(
                'form'              => $form->getData(),
                'autocomplete_keys' => get_object_vars($data->autocomplete_keys));
            $features = $engine->search($conf, $query, $request->get('srs'), $request->get('extent'));
            $result   = $this->getFeatureCollection($features);
            $response->setData(array_merge($result, array(
                'query' => $query['form']
            )));

            return $response;
        }

        throw new NotFoundHttpException();
    }

    /**
     * Create form for selecting search route (= search form) to display.
     *
     * @return Form Search route select form
     */
    public function getRouteSelectForm()
    {
        $configuration = $this->getConfiguration();
        $formFactory   = $this->container->get('form.factory');
        $form          = $formFactory->createNamed(
            'search_routes',
            new SearchRouterSelectType(),
            null,
            array('routes' => $configuration['routes'])
        );
        return $form->createView();
    }

    /**
     * Get all forms.
     *
     * @return Form[] Search forms
     */
    public function getForms()
    {
        if (!$this->forms) {
            $configuration = $this->getConfiguration();
            $formFactory   = $this->container->get('form.factory');
            foreach ($configuration['routes'] as $name => $conf) {
                $this->forms[ $name ] = $formFactory->createNamed(
                    $name,
                    new SearchRouterFormType(),
                    null, // data
                    array('fields' => $conf)
                );
            }
        }
        return $this->forms;
    }

    /**
     * Get form views
     *
     * @return FormView[]
     */
    public function getFormViews()
    {
        $formViews = array();
        $forms     = $this->getForms();
        if ($forms) {
            foreach ($forms as $form) {
                $formViews[] = $form->createView();
            }
        }
        return $formViews;
    }

    /**
     * @inheritdoc
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        if (key_exists('dialog', $configuration)) {
            $configuration['asDialog'] = $configuration['dialog'];
            unset($configuration['dialog']);
        }
        if (key_exists('timeout', $configuration)) {
            $configuration['timeoutFactor'] = $configuration['timeout'];
            unset($configuration['timeout']);
        }
        foreach ($configuration['routes'] as $routekey => $routevalue) {
            if (key_exists('configuration', $routevalue)) {
                foreach ($configuration['routes'][ $routekey ]['configuration'] as $key => $value) {
                    $configuration['routes'][ $routekey ][ $key ] = $value;
                }
                unset($configuration['routes'][ $routekey ]['configuration']);
            }
        }
        return $configuration;
    }

    /**
     * GeoJSON FeatureCollection
     *
     * @param array $features
     * @return array
     */
    protected function getFeatureCollection(&$features)
    {
        return array(
            'type'     => 'FeatureCollection',
            'features' => $features
        );
    }

    /**
     * Get the publicly exposed configuration, usually directly derived from
     * the configuration field of the configuration entity. If you, for
     * example, store passwords in your element configuration, you should
     * override this method to return a cleaned up version of your
     * configuration which can safely be exposed in the client.
     *
     * @return array
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        $routes        = &$configuration["routes"];
        foreach ($routes as $k => $route) {
            $routes[ $k ] = static::mergeArrays(static::$defaultRouteConfiguration, $route);
        }
        return $configuration;
    }
}
