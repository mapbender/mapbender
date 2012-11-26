<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\Response;
use Mapbender\CoreBundle\Element\Type\SearchRouterSelectType;
use Mapbender\CoreBundle\Element\Type\SearchRouterFormType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * SearchRouter element.
 *
 *
 *
 *  routes:
 *      test:
 *          title: Test search Route
 *          class: Acme\TestSearchEngine
 *          form: # Declare form elements here
 *              nav_point: # element name
 *                  type: text # element type
 *                  options: # form options
 *                      required: true # false by default
 * 
 * @author Christian Wygoda
 */
class SearchRouter extends Element
{
    protected $forms;

    static public function getClassTitle()
    {
        return "Search Router";
    }

    static public function getClassDescription()
    {
        return "Configurable search routing element";
    }

    static public function getClassTags()
    {
        return array('Search', 'Router');
    }

    public function getWidgetName()
    {
        return 'mapbender.mbSearchRouter';
    }

    public function getAssets()
    {
        return array(
            'js' => array('mapbender.element.searchRouter.js'),
            'css' => array('mapbender.element.searchRouter.css'));
    }

    public function httpAction($action)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $request = $this->container->get('request');

        if('autocomplete' === $action) {
            // Get search config
            parse_str($request->get('target'), $target_array);
            $conf_id = array_keys($target_array);
            $conf_id = $conf_id[0];

            $conf = $this->getConfiguration();
            if(!array_key_exists($conf_id, $conf['routes'])) {
                throw new NotFoundHttpException();
            }
            $conf = $conf['routes'][$conf_id];
            $engine = new $conf['class']($this->container);

            $target = substr($request->get('target'), strlen($conf_id));
            parse_str($request->get('data'), $data);

            $response->setContent(json_encode($engine->autocomplete(
                $target,
                $request->get('term'),
                $data[$conf_id],
                $request->get('srs'),
                $request->get('extent'))));
            return $response;
        }

        if('search' === $action) {
            $target = $request->get('target');
            
            $conf = $this->getConfiguration();
            if(!array_key_exists($target, $conf['routes'])) {
                throw new NotFoundHttpException();
            }

            $this->setupForms();
            $form = $this->forms[$target];
            parse_str($request->get('data'), $data);

            $form->bind($data[$target]);

            parse_str($request->get('autocomplete_keys'), $autocomplete_keys);

            $conf = $conf['routes'][$target];
            $engine = new $conf['class']($this->container);
            $response->setContent(json_encode($engine->search(
                $conf,
                array(
                    'form' => $form->getData(),
                    'autocomplete' => $autocomplete_keys
                ),
                $request->get('srs'),
                $request->get('extent'))));
            return $response;
        }

        throw new NotFoundHttpException();
    }

    public function render()
    {
        return $this->container->get('templating')
            ->render('MapbenderCoreBundle:Element:search_router.html.twig',
                array('element' => $this));
    }

    /**
     * Create form for selecting search route (= search form) to display.
     * 
     * @return Symfony\Component\Form\Form Search route select form
     */
    public function getRouteSelectForm() {
        $configuration = $this->getConfiguration();

        $form = $this->container->get('form.factory')->createNamed(
            'search_routes',
            new SearchRouterSelectType(),
            null, array('routes' => $configuration['routes']));

        return $form->createView();
    }

    /**
     * Set up all search forms.
     */
    protected function setupForms()
    {
        if(null === $this->forms) {
            $configuration = $this->getConfiguration();
            foreach($configuration['routes'] as $name => $conf) {
                $this->forms[$name] = $this->setupForm($name, $conf);
            }
        }
    }

    /**
     * Get all forms.
     * @return array Search forms
     */
    public function getForms() {
        if(null === $this->forms) {
            $this->setupForms();
        }

        return $this->forms;
    }

    /**
     * Set up a single form.
     * 
     * @param  string $name Form name for FormBuilder
     * @param  array  $conf Search form configuration
     * @return [type]       Form
     */
    protected function setupForm($name, array $conf) {
        $form = $this->container->get('form.factory')->createNamed(
            $name,
            new SearchRouterFormType(),
            null, array('fields' => $conf));

        return $form;
    }


}
