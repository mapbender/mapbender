<?php

namespace Mapbender\CoreBundle\Element;

use Doctrine\Persistence\ManagerRegistry;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Component\ElementInventoryService;

class InteractiveHelp extends AbstractElementService
{
    public function __construct(
        protected ManagerRegistry $doctrine,
        protected ElementInventoryService $elementInventory
    ) {
    }

    public static function getClassTitle()
    {
        return 'mb.interactivehelp.element.title';
    }

    public static function getClassDescription()
    {
        return 'mb.interactivehelp.element.description';
    }

    public function getWidgetName(Element $element)
    {
        return 'MbInteractiveHelp';
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/elements/MbInteractiveHelp.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/interactivehelp.scss',
            ),
            'trans' => array(
                'mb.interactivehelp.*',
            ),
       );
    }

    public static function getDefaultConfiguration()
    {
        return array(
            'autoOpen' => false,
            'tour' => array(
                'intro' => array(
                    'title' => 'mb.interactivehelp.intro.title',
                    'description' => 'mb.interactivehelp.intro.description',
                ),
                'chapters' => array(
                    array(
                        'title' => 'mb.interactivehelp.applicationswitcher.title',
                        'description' => 'mb.interactivehelp.applicationswitcher.description',
                        'type' => 'Mapbender\CoreBundle\Element\ApplicationSwitcher',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.basesourceswitcher.title',
                        'description' => 'mb.interactivehelp.basesourceswitcher.description',
                        'type' => 'Mapbender\CoreBundle\Element\BaseSourceSwitcher',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.coordinatesdisplay.title',
                        'description' => 'mb.interactivehelp.coordinatesdisplay.description',
                        'type' => 'Mapbender\CoreBundle\Element\CoordinatesDisplay',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.coordinatesutility.title',
                        'description' => 'mb.interactivehelp.coordinatesutility.description',
                        'type' => 'Mapbender\CoreBundle\Element\CoordinatesUtility',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.dataupload.title',
                        'description' => 'mb.interactivehelp.dataupload.description',
                        'type' => 'Mapbender\CoreBundle\Element\DataUpload',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.featureinfo.title',
                        'description' => 'mb.interactivehelp.featureinfo.description',
                        'type' => 'Mapbender\CoreBundle\Element\FeatureInfo',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.gpsposition.title',
                        'description' => 'mb.interactivehelp.gpsposition.description',
                        'type' => 'Mapbender\CoreBundle\Element\GpsPosition',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.layertree.title',
                        'description' => 'mb.interactivehelp.layertree.description',
                        'type' => 'Mapbender\CoreBundle\Element\LayerTree',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.legend.title',
                        'description' => 'mb.interactivehelp.legend.description',
                        'type' => 'Mapbender\CoreBundle\Element\Legend',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.overview.title',
                        'description' => 'mb.interactivehelp.overview.description',
                        'type' => 'Mapbender\CoreBundle\Element\Overview',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.poi.title',
                        'description' => 'mb.interactivehelp.poi.description',
                        'type' => 'Mapbender\CoreBundle\Element\Poi',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.printclient.title',
                        'description' => 'mb.interactivehelp.printclient.description',
                        'type' => 'Mapbender\PrintBundle\Element\PrintClient',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.ruler.title',
                        'description' => 'mb.interactivehelp.ruler.description',
                        'type' => 'Mapbender\CoreBundle\Element\Ruler',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.scaledisplay.title',
                        'description' => 'mb.interactivehelp.scaledisplay.description',
                        'type' => 'Mapbender\CoreBundle\Element\ScaleDisplay',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.scaleselector.title',
                        'description' => 'mb.interactivehelp.scaleselector.description',
                        'type' => 'Mapbender\CoreBundle\Element\ScaleSelector',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.sketch.title',
                        'description' => 'mb.interactivehelp.sketch.description',
                        'type' => 'Mapbender\CoreBundle\Element\Sketch',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.srsselector.title',
                        'description' => 'mb.interactivehelp.srsselector.description',
                        'type' => 'Mapbender\CoreBundle\Element\SrsSelector',
                    ),
                    array(
                        'title' => 'mb.interactivehelp.wmsloader.title',
                        'description' => 'mb.interactivehelp.wmsloader.description',
                        'type' => 'Mapbender\WmsBundle\Element\WmsLoader',
                    ),
                ),
            ),
            "element_icon" => self::getDefaultIcon(),
        );
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\InteractiveHelpAdminType';
    }

    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/interactivehelp.html.twig';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderCore/Element/interactivehelp.html.twig');
        $view->attributes['class'] = 'mb-element-interactivehelp';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['config'] = $element->getConfiguration();
        $view->variables['id'] = $element->getId();
        return $view;
    }

    public function getClientConfiguration(Element $element)
    {
        $config = $element->getConfiguration() ?: [];
        $allElements = $element->getApplication()->getElements();
        foreach ($config['tour']['chapters'] as $key => $chapter) {
            $filteredElements = $allElements->filter(function (Element $element) use ($chapter) {
                return $element->getClass() === $chapter['type'];
            });
            foreach ($filteredElements as $e) {
                if ($e) {
                    $handler = $this->elementInventory->getHandlerService($e);
                    $config['tour']['chapters'][$key]['class'] = $handler->getWidgetName($e);
                    $classAttr = $handler->getView($e)->attributes['class'] ?? '';
                    switch (true) {
                        case preg_match('/mb-element-([a-z]+)/i', $classAttr, $matches):
                            $config['tour']['chapters'][$key]['selector'] = $matches[0];
                            break;
                        case str_contains($classAttr, 'mb-gpsButton'):
                            $config['tour']['chapters'][$key]['selector'] = 'mb-gpsButton';
                            break;
                        case str_contains($classAttr, 'mb-aboutButton'):
                            $config['tour']['chapters'][$key]['selector'] = 'mb-aboutButton';
                            break;
                        default:
                            $config['tour']['chapters'][$key]['selector'] = '';
                    }
                    $config['tour']['chapters'][$key]['region'] = $e->getRegion();
                }
            }
        }
        return $config;
    }

    public static function getDefaultIcon()
    {
        return 'iconBookOpen';
    }
}
