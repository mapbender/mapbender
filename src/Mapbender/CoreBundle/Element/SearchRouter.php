<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\SQLSearchEngine;
use Mapbender\ManagerBundle\Component\Mapper;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
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

    public function getWidgetName()
    {
        return 'mapbender.mbSearchRouter';
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

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:search_router.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js'    => array(
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js',
                '@MapbenderCoreBundle/Resources/public/mapbender.element.searchRouter.js',
            ),
            'css'   => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/search_router.scss',
            ),
            'trans' => array(
                'MapbenderCoreBundle:Element:search_router.json.twig',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        /** @var SQLSearchEngine $engine */
        $request = $this->container->get('request_stack')->getCurrentRequest();

        $actionParts = explode('/', $action);
        if (count($actionParts) !== 2) {
            throw new NotFoundHttpException();
        }
        $categoryId = $actionParts[0];
        $action = $actionParts[1];

        $categoryConf = $this->getCategoryConfig($categoryId);
        if (!$categoryConf) {
            throw new NotFoundHttpException();
        }
        if ('autocomplete' === $action) {
            $data = json_decode($request->getContent(), true);
            $engine  = new $categoryConf['class']($this->container);
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
            $this->getForms();
            $data = json_decode($request->getContent(), true);
            $form = $this->getForm($categoryConf, $categoryId);
            $form->submit($data['properties']);
            $engine   = new $categoryConf['class']($this->container);
            $query    = array(
                'form' => $form->getData(),
            );
            $features = $engine->search($categoryConf, $query, $data['srs'], $data['extent']);
            return new JsonResponse(array(
                'type'     => 'FeatureCollection',
                'features' => $features,
            ));
        }

        throw new NotFoundHttpException();
    }

    /**
     * Create form for selecting search route (= search form) to display.
     *
     * @return FormView Search route select form
     */
    public function getRouteSelectForm()
    {
        $configuration = $this->getConfiguration();
        /** @var FormFactoryInterface $formFactory */
        $formFactory   = $this->container->get('form.factory');
        $form          = $formFactory->createNamed(
            'search_routes',
            'Mapbender\CoreBundle\Element\Type\SearchRouterSelectType',
            null,
            array('routes' => $configuration['routes'])
        );
        return $form->createView();
    }

    /**
     * @param $categoryConfig
     * @param $categoryId
     * @return FormInterface
     */
    protected function getForm($categoryConfig, $categoryId)
    {
        /** @var FormFactoryInterface $factory */
        $factory = $this->container->get('form.factory');
        return $factory->createNamed($categoryId, 'Mapbender\CoreBundle\Element\Type\SearchRouterFormType', null, array(
            'fields' => $categoryConfig,
        ));
    }

    /**
     * Get all forms.
     *
     * @return Form[] Search forms
     */
    public function getForms()
    {
        $forms = array();
        $configuration = $this->getConfiguration();
        foreach ($configuration['routes'] as $name => $conf) {
            $forms[$name] = $this->getForm($conf, $name);
        }
        // Legacy / inheritance compatibility HACK: store forms in instance attribute
        $this->forms = $forms;
        return $forms;
    }

    /**
     * Get form views
     *
     * @return FormView[]
     */
    public function getFormViews()
    {
        $formViews = array();
        $configuration = $this->getConfiguration();
        foreach ($configuration['routes'] as $name => $conf) {
            $formViews[] = $this->getForm($conf, $name)->createView();
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
        foreach (array_keys($configuration['routes']) as $categoryId) {
            $withDefaults = $this->getCategoryConfig($categoryId);
            $configuration['routes'][$categoryId] = $withDefaults;
        }
        return $configuration;
    }

    /**
     * @param string $categoryId
     * @return array|null
     */
    protected function getCategoryConfig($categoryId)
    {
        $config = $this->entity->getConfiguration();
        if (empty($config['routes'][$categoryId])) {
            return null;
        } else {
            $defaults = $this->getDefaultRouteConfiguration();
            return array_replace_recursive($defaults, $config['routes'][$categoryId]);
        }
    }
}
