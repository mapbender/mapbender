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


        switch ($action) {
            case 'select':
                $defaultCriteria = array('returnType' => 'FeatureCollection',
                                         'maxResults' => 2);
                $results         = $featureType->search(array_merge($defaultCriteria, $request));
                break;

            case 'save':
                // save once
                if(isset($request['feature'])){
                    $request['features'] = array($featureType->save($request['feature']));
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

        if ('select' === $action) {
            $data = json_decode($request->getContent(), true);
            $schema = $configuration['schemes'][$data['schemaName']];

            $sql = "SELECT *, ST_AsGeoJSON(ST_Transform(" . $schema['geomColumn'] . ", :clientSrs)) as geom"
                . " FROM ". $schema['table']
                . " WHERE " . $schema['geomColumn'] . " && ST_MakeEnvelope(:left,:bottom,:right,:top, :dbSrs)";

            $conn = $this->container->get('doctrine.dbal.' . $schema['connection'] . '_connection');
            $stmt = $conn->prepare($sql);
            $stmt->bindValue('clientSrs', $data['clientSrid']);
            $stmt->bindValue('dbSrs', $schema['srid']);
            $stmt->bindValue('left', $data['left']);
            $stmt->bindValue('bottom', $data['bottom']);
            $stmt->bindValue('right', $data['right']);
            $stmt->bindValue('top', $data['top']);
            $stmt->execute();

            $geoJson = array('type' => 'FeatureCollection',
               'features' => array()
            );

            $c = 1;
            while ($row = $stmt->fetch()) {
                foreach ($row as $key => $value) {
                    if (isset($schema['columns'][$key]['tab'])) {
                        if($schema['columns'][$key]['tab'] === true){
                            $tableData[$c][$key] = $value;
                        }
                    }
                }

                $tempData = $row;
                unset($tempData[$schema['geomColumn']]);
                $geoJson['features'][] = array(
                    'type' => 'Feature',
                    'geometry' => json_decode($row['geom']),
                    'properties' => $tempData
                );
                $c++;
            }

            return new JsonResponse($geoJson);
        }
    }
}