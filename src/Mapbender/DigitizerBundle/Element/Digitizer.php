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
        $request = $this->container->get('request');
        $configuration = $this->getConfiguration();

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