<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Element\Type\SearchRouterFormType;
use Mapbender\CoreBundle\Element\Type\SearchRouterSelectType;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * SearchRouter element.
 *
 * @author Christian Wygoda
 */
class SearchRouter extends Element
{
    /**
     *
     * @var type
     */
    protected $forms;

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.searchrouter.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.searchrouter.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.searchrouter.tag.search",
            "mb.core.searchrouter.tag.router");
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbSearchRouter';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => 'Search Router',
            "dialog" => false,
            "target" => null,
            'timeoutFactor' => 3,
            "width" => 700,
            "height" => 500
        );
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapquery/lib/openlayers/OpenLayers.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
                '/components/json2/json2.js',
                '/components/backbone/backbone-min.js',
                'mapbender.element.searchRouter.Feature.js',
                'mapbender.element.searchRouter.Search.js',
                'mapbender.element.searchRouter.js'),
            'css' => array('@MapbenderCoreBundle/Resources/public/sass/element/search_router.scss'),
            'trans' => array('MapbenderCoreBundle:Element:search_router.json.twig'));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
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

            $response->setContent(json_encode(array(
                'key' => $data->key,
                'value' => $data->value,
                'properties' => $data->properties,
                'results' => $results
            )));
            return $response;
        }

        if ('search' === $action) {
            $this->setupForms();
            $form = $this->forms[$target];
            $data = json_decode($request->getContent());
            $form->bind(get_object_vars($data->properties));

            $conf = $conf['routes'][$target];
            $engine = new $conf['class']($this->container);
            $query = array(
                'form' => $form->getData(),
                'autocomplete_keys' => get_object_vars($data->autocomplete_keys)
            );
            $features = $engine->search($conf, $query, $request->get('srs'), $request->get('extent'));

            // Return GeoJSON FeatureCollection
            $response->setContent(json_encode(array(
                'type' => 'FeatureCollection',
                'features' => $features,
                'query' => $query['form'])));
            return $response;
        }

        throw new NotFoundHttpException();
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderCoreBundle:Element:search_router.html.twig', array('element' => $this));
    }

    /**
     * Create form for selecting search route (= search form) to display.
     *
     * @return Symfony\Component\Form\Form Search route select form
     */
    public function getRouteSelectForm()
    {
        $configuration = $this->getConfiguration();

        $form = $this->container->get('form.factory')->createNamed(
            'search_routes',
            new SearchRouterSelectType(),
            null,
            array('routes' => $configuration['routes'])
        );

        return $form->createView();
    }

    /**
     * Set up all search forms.
     */
    protected function setupForms()
    {
        if (null === $this->forms) {
            $configuration = $this->getConfiguration();
            foreach ($configuration['routes'] as $name => $conf) {
                $this->forms[$name] = $this->setupForm($name, $conf);
            }
        }
    }

    /**
     * Get all forms.
     * @return array Search forms
     */
    public function getForms()
    {
        if (null === $this->forms) {
            $this->setupForms();
        }

        return $this->forms;
    }

    public function getFormViews()
    {
        $formViews = array();
        $forms     = $this->getForms();
        if($forms){
            foreach ($forms as $form) {
                $formViews[] = $form->createView();
            }
        }

        return $formViews;
    }

    /**
     * Set up a single form.
     *
     * @param  string $name Form name for FormBuilder
     * @param  array  $conf Search form configuration
     * @return [type]       Form
     */
    protected function setupForm($name, array $conf)
    {
        $form = $this->container->get('form.factory')
            ->createNamed($name, new SearchRouterFormType(), null, array('fields' => $conf));

        return $form;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\SearchRouterAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:search_router.html.twig';
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
}
