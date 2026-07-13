<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\PrintBundle\Element\PrintClient;
use Mapbender\WmsBundle\Element\WmsLoader;
use Mapbender\CoreBundle\Element\Type\InteractiveHelpAdminType;
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

    public static function getClassTitle(): string
    {
        return 'mb.interactivehelp.element.title';
    }

    public static function getClassDescription(): string
    {
        return 'mb.interactivehelp.element.description';
    }

    public function getWidgetName(Element $element): string
    {
        return 'MbInteractiveHelp';
    }

    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbInteractiveHelp.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/interactivehelp.scss',
            ],
            'trans' => [
                'mb.interactivehelp.*',
            ],
       ];
    }

    public static function getDefaultConfiguration(): array
    {
        return [
            'autoOpen' => false,
            'tour' => [
                'intro' => [
                    'title' => 'mb.interactivehelp.intro.title',
                    'description' => 'mb.interactivehelp.intro.description',
                ],
                'chapters' => [
                    [
                        'title' => 'mb.interactivehelp.applicationswitcher.title',
                        'description' => 'mb.interactivehelp.applicationswitcher.description',
                        'type' => ApplicationSwitcher::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.basesourceswitcher.title',
                        'description' => 'mb.interactivehelp.basesourceswitcher.description',
                        'type' => BaseSourceSwitcher::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.coordinatesdisplay.title',
                        'description' => 'mb.interactivehelp.coordinatesdisplay.description',
                        'type' => CoordinatesDisplay::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.coordinatesutility.title',
                        'description' => 'mb.interactivehelp.coordinatesutility.description',
                        'type' => CoordinatesUtility::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.dataupload.title',
                        'description' => 'mb.interactivehelp.dataupload.description',
                        'type' => DataUpload::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.featureinfo.title',
                        'description' => 'mb.interactivehelp.featureinfo.description',
                        'type' => FeatureInfo::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.gpsposition.title',
                        'description' => 'mb.interactivehelp.gpsposition.description',
                        'type' => GpsPosition::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.layertree.title',
                        'description' => 'mb.interactivehelp.layertree.description',
                        'type' => LayerTree::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.legend.title',
                        'description' => 'mb.interactivehelp.legend.description',
                        'type' => Legend::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.overview.title',
                        'description' => 'mb.interactivehelp.overview.description',
                        'type' => Overview::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.poi.title',
                        'description' => 'mb.interactivehelp.poi.description',
                        'type' => Poi::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.printclient.title',
                        'description' => 'mb.interactivehelp.printclient.description',
                        'type' => PrintClient::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.ruler.title',
                        'description' => 'mb.interactivehelp.ruler.description',
                        'type' => Ruler::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.scaledisplay.title',
                        'description' => 'mb.interactivehelp.scaledisplay.description',
                        'type' => ScaleDisplay::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.scaleselector.title',
                        'description' => 'mb.interactivehelp.scaleselector.description',
                        'type' => ScaleSelector::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.sketch.title',
                        'description' => 'mb.interactivehelp.sketch.description',
                        'type' => Sketch::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.srsselector.title',
                        'description' => 'mb.interactivehelp.srsselector.description',
                        'type' => SrsSelector::class,
                    ],
                    [
                        'title' => 'mb.interactivehelp.wmsloader.title',
                        'description' => 'mb.interactivehelp.wmsloader.description',
                        'type' => WmsLoader::class,
                    ],
                ],
            ],
            "element_icon" => self::getDefaultIcon(),
        ];
    }

    public static function getType(): string
    {
        return InteractiveHelpAdminType::class;
    }

    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/interactivehelp.html.twig';
    }

    public function getView(Element $element): TemplateView
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
            $filteredElements = $allElements->filter(fn(Element $element): bool => $element->getClass() === $chapter['type']);
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

    public static function getDefaultIcon(): string
    {
        return 'iconBookOpen';
    }
}
