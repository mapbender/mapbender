<?php

namespace Mapbender\DigitizerBundle\Element;

use Mapbender\CoreBundle\Element\HTMLElement;
use Mapbender\CoreBundle\Entity\FeatureType;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 *
 */
class Digitizer extends HTMLElement
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
        return array('js'    => array(
            'mapbender.element.digitizer.js',
            '@FOMCoreBundle/Resources/public/js/widgets/popup.js',
            '@FOMCoreBundle/Resources/public/js/widgets/dropdown.js'),
                     'css'   => array('sass/element/digitizer.scss'),
                     'trans' => array(
                         '@MapbenderDigitizerBundle/Resources/views/Element/digitizer.json.twig'
                     ));
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
     * Prepare form items for each scheme definition
     * Optional: get featureType by name from global context.
     *
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        if ($configuration["schemes"] && is_array($configuration["schemes"])) {
            foreach ($configuration["schemes"] as $key => &$scheme) {
                if (is_string($scheme['featureType'])) {
                    $featureTypes          = $this->container->getParameter('featureTypes');
                    $scheme['featureType'] = $featureTypes[$scheme['featureType']];
                }
                if (isset($scheme['formItems'])) {
                    $scheme['formItems'] = $this->prepareItems($scheme['formItems']);
                }
            }
        }
        return $configuration;
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

        if(is_array($schema['featureType'])){
            $featureType = new FeatureType($this->container, $schema['featureType']);
        }else{
            throw new Exception("FeatureType settings not correct");
        }

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

            case 'delete':
                // remove once
                $results =  $featureType->remove($request['feature'])->getId();

        }

        return new JsonResponse($results);
    }
}