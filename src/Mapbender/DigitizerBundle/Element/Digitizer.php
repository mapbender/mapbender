<?php

namespace Mapbender\DigitizerBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 */
class Digitizer extends Element
{

    /**
     * @inheritdoc
     */
    static public function getClassTitle()
    {
        return "Digitizer";
    }

    /**
     * @inheritdoc
     */
    static public function getClassDescription()
    {
        return "Digitizer";
    }

    /**
     * @inheritdoc
     */
    static public function getTags()
    {
        return array();
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbDigitizer';
    }

    /**
     * @inheritdoc
     */
    static public function listAssets()
    {
        return array('js' => array('mapbender.element.digitizer.js',
                '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
                '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
            'css' => array('sass/element/digitizer.scss'),
            'trans' => array()
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "target" => null
        );
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $config = parent::getConfiguration();
        return $config;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\DigitizerBundle\Element\Type\DigitizerAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderDigitizerBundle:ElementAdmin:digitizeradmin.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')
                ->render('MapbenderDigitizerBundle:Element:digitizer.html.twig',
                    array(
                    'id' => $this->getId(),
                    'title' => $this->getTitle(),
                    'configuration' => $this->getConfiguration()
        ));
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        $configuration = $this->getConfiguration();
        $request       = json_decode($this->container->get('request')->getContent(), true);
        $schemas       = $configuration["schemes"];
        $schema        = $schemas[$request["schema"]];
        $features      = $this->container->get('features');
        $featureType   = $features->get($schema['featureType']);
        $results       = array();

        switch ($action) {
            case 'select':
                $defaultCriteria = array('returnType' => 'FeatureCollection',
                                         'maxResults' => 2);
                $results         = $featureType->search(array_merge($defaultCriteria, $request));
                break;

            case 'save':
                // save once
                if(isset($request['feature'])){
                    $request['features'] = array($request['feature']);
                }
                // save collection
                if(isset($request['features']) && is_array($request['features'])){
                    foreach ($request['features'] as $feature) {
                        $results[] = $featureType->save($feature);
                    }
                }
                $results = $featureType->toFeatureCollection($results);
                break;

            case 'remove':
                // remove once
                if(isset($request['feature'])){
                    $results[] =  array($featureType->remove($request['feature']));
                }
                $results = $featureType->toFeatureCollection($results);
                break;
        }

        return new JsonResponse($results);
    }
}