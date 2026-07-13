<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\GpsPositionAdminType;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;

class GpsPosition extends ButtonLike implements ConfigMigrationInterface
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.gpsposition.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.gpsposition.class.description";
    }

    /**
     * @return mixed[]
     */
    public function getRequiredAssets(Element $element): array
    {
        $required = parent::getRequiredAssets($element) + [
            'js' => [],
            'trans' => [],
        ];
        $required['js'] = array_merge($required['js'], [
            '@MapbenderCoreBundle/Resources/public/elements/MbGpsPosition.js',
            // Uncomment to enable Geolocation API mock
            // '@MapbenderCoreBundle/Resources/public/GeolocationMock.js',
        ]);
        $required['trans'] = array_merge($required['trans'], [
            'mb.core.gpsposition.*',
        ]);
        return $required;
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return GpsPositionAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return array_replace(parent::getDefaultConfiguration(), [
            'autoStart'             => false,
            'icon' => 'iconGps',
            'average'               => 1,
            'follow'                => false,
            'centerOnFirstPosition' => true,
            'zoomToAccuracyOnFirstPosition' => true,
            'element_icon' => self::getDefaultIcon(),
        ]);
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbGpsPosition';
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/gpsposition.html.twig');
        $this->initializeView($view, $element);
        $view->attributes['class'] = 'mb-button mb-gpsButton';
        return $view;

    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/gpsposition.html.twig';
    }

    public static function updateEntityConfig(Element $entity): void
    {
        $config = $entity->getConfiguration() ?: [];
        if (!empty($config['zoomToAccuracy']) && isset($config['centerOnFirstPosition'])) {
            $config['zoomToAccuaryOnFirstPosition'] = $config['centerOnFirstPosition'];
        }
        unset($config['zoomToAccuracy']);
        $entity->setConfiguration($config);
    }
    public static function getDefaultIcon(): string
    {
        return 'iconGpsTarget';
    }
}
