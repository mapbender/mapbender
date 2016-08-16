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
    protected static $title       = "mb.core.searchrouter.class.title";
    protected static $description = "mb.core.searchrouter.class.description";

    /** @var string Element title translation subject */
    protected static $tags = array(
        "mb.core.searchrouter.tag.search",
        "mb.core.searchrouter.tag.router"
    );

    /** @var array Element default configuration */
    protected static $defaultConfiguration = array(
        'tooltip'       => "mb.core.searchrouter.class.title",
        'title'         => "mb.core.searchrouter.class.title",
        "target"        => null,
        'timeoutFactor' => 3,
        "width"         => 700,
        "height"        => 500,
        "dialog"        => false, // for what???
        "asDialog"      => false,
    );

    /** @var Form[] Element search forms */
    protected $forms;

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js'    => array(
                '@MapbenderCoreBundle/Resources/public/mapquery/lib/openlayers/OpenLayers.js',
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
            $conf = $conf['routes'][$target];
            $engine = new $conf['class']($this->container);
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
                foreach ($configuration['routes'][$routekey]['configuration'] as $key => $value) {
                    $configuration['routes'][$routekey][$key] = $value;
                }
                unset($configuration['routes'][$routekey]['configuration']);
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
}
