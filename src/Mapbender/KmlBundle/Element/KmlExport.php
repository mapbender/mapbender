<?php
namespace Mapbender\KmlBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

class KmlExport extends Element {

    public static function getClassTitle() {
        return "mb.kml.kmlexport.class.title";
    }

    public static function getClassDescription() {
        return "mb.kml.kmlexport.class.description";
    }

    public static function getClassTags() {
        return array(
            "mb.kml.kmlexport.tag.kml",
            "mb.kml.kmlexport.tag.export"
        );
    }


    public function getWidgetName() {
        return 'mapbender.mbKmlExport';
    }

    static public function listAssets() {
        return array(
            'js' => array(
                'mapbender.element.kmlexport.js'
            ),
            'css' => array()
        );
    }

    public function getConfiguration() {
        $opts = array();
        $opts['text'] = $this->getClassTitle();
        /* // Resolve the run-time id of the target widget */
        /* if(array_key_exists('target', $this->configuration)) { */
        /*     $elementId = $this->configuration['target']; */
        /*     $finalId = $this->application->getFinalId($elementId); */
        /*     $opts = array_merge($opts, array('target' => $finalId)); */
        /* } */
        return array(
            'options' => $opts,
            'init' => 'mbKmlExport',
        );
    }

    public function httpAction($action) {
        switch($action) {
        case 'mapexport':
            return $this->map2Kml();
        }
    }

    private function map2Kml() {
        $response = new Response();

        $layers = $this->container->get('request')->get('layers');
        foreach($layers as $title => &$layer) {
            parse_str($layer, $layer);

            /* $layer['params']['LAYERS'] = implode(',', */
            /*     $layer['options']['layers']); */

            $layer['params']['WIDTH'] = 512;
            $layer['params']['HEIGHT'] = 512;
            $layer['params']['SRS'] = 'EPSG:4326';

            $delimiter = strpos($layer['options']['url'], '?') === False ?
                '?' : '&';
            $layer['getMap'] = $layer['options']['url'] . $delimiter
                . http_build_query($layer['params']);
        }

        // IMPORTANT: THIS DEPENDS ON THE php5-mapscript EXTENSION
        $extent = new \rectObj();
        $extentIn = explode(',', $this->container->get('request')->get('extent'));
        $extent->setExtent($extentIn[0], $extentIn[1], $extentIn[2], $extentIn[3]);

        $srs = $this->container->get('request')->get('srs');
        $srsFrom = new \projectionObj($srs);
        $srsTo = new \projectionObj('EPSG:4326');
        $extent->project($srsFrom, $srsTo);

        $xml = $this->container->get('templating')
            ->render('MapbenderKmlBundle:Element:kmlexport_map.kml.twig',
                array('layers' => $layers, 'extent' => array(
                    'minx' => $extent->minx,
                    'miny' => $extent->miny,
                    'maxx' => $extent->maxx,
                    'maxy' => $extent->maxy,
                )));

        $response->setContent($xml);
        $response->headers->set('Content-Type',
            'application/vnd.google-earth.kml+xml');
        $response->headers->set('Content-Disposition',
            'attachment; filename="bkg.kml"');
        return $response;
    }

    public function render() {
        return $this->container->get('templating')
            ->render('MapbenderKmlBundle:Element:kmlexport.html.twig', array(
            'id' => $this->getId(),
            'application' => $this->application->getSlug(),
            'configuration' => $this->getConfiguration(),
            'label' => $this->getClassTitle()));
    }
}
