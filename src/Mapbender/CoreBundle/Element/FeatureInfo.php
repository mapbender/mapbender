<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ColorUtils;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * Featureinfo element
 *
 * This element will provide feature info for most layer types
 *
 * @author Christian Wygoda
 */
class FeatureInfo extends AbstractElementService
    implements ConfigMigrationInterface
{
    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.featureinfo.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.featureinfo.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getClientConfiguration(Element $element)
    {
        $config = $element->getConfiguration();
        $defaults = self::getDefaultConfiguration();
        // Amend config values with null defaults to working values
        if (empty($config['width'])) {
            $config['width'] = $defaults['width'];
        }
        if (empty($config['height'])) {
            $config['height'] = $defaults['height'];
        }
        if (empty($config['maxCount']) || $config['maxCount'] < 0) {
            $config['maxCount'] = $defaults['maxCount'];
        }
        return $config + $defaults;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            "autoActivate" => false,
            "deactivateOnClose" => true,
            "printResult" => false,
            "onlyValid" => false,
            "displayType" => 'tabs',
            "width" => 700,
            "height" => 500,
            "maxCount" => 100,
            'highlighting' => false,
            'fillColorDefault' => 'rgba(255,165,0,0.4)',
            'fillColorHover' => 'rgba(255,0,0,0.7)',
            'strokeColorDefault' => 'rgba(255,68,102,0.4)',
            'strokeColorHover' => 'rgba(255,0,0,0.7)',
            'strokeWidthDefault' => 1,
            'strokeWidthHover' => 3,
            'pointRadiusDefault' => 7,
            'pointRadiusHover' => 9,
            'fontColorDefault' => '#000000',
            'fontColorHover' => '#000000',
            'fontSizeDefault' => 12,
            'fontSizeHover' => 12,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbFeatureInfo';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\FeatureInfoAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.featureInfo.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/featureinfo.scss',
            ),
            'trans' => array(
                'mb.core.featureinfo.error.*',
            ),
        );
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderCore/Element/featureinfo.html.twig');
        $view->attributes['class'] = 'mb-element-featureinfo';
        $view->attributes['data-title'] = $element->getTitle();
        $config = $element->getConfiguration() ?: array();
        $view->variables['displayType'] = ArrayUtil::getDefault($config, 'displayType', 'tabs');
        $view->variables['iframe_scripts'] = array(
            file_get_contents(__DIR__ . '/../Resources/public/element/featureinfo-mb-action.js'),
        );
        if (!empty($config['highlighting'])) {
            $view->variables['iframe_scripts'][] = file_get_contents(__DIR__ . '/../Resources/public/element/featureinfo-highlighting.js');
        }
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/featureinfo.html.twig';
    }

    public static function updateEntityConfig(Element $entity)
    {
        $config = $entity->getConfiguration();
        if (!empty($config['featureColorDefault'])) {
            $config += array('fillColorDefault' => $config['featureColorDefault']);
        }
        if (!empty($config['featureColorHover'])) {
            $config += array('fillColorHover' => $config['featureColorHover']);
        }
        unset($config['featureColorDefault']);
        unset($config['featureColorHover']);
        if (!empty($config['opacityDefault'])) {
            $config['fillColorDefault'] = ColorUtils::addOpacityToColor($config, 'fillColorDefault', 'opacityDefault');
            $config['strokeColorDefault'] = ColorUtils::addOpacityToColor($config, 'strokeColorDefault', 'opacityDefault');
        }
        if (!empty($config['opacityHover'])) {
            $config['fillColorHover'] = ColorUtils::addOpacityToColor($config, 'fillColorHover', 'opacityHover');
            $config['strokeColorHover'] = ColorUtils::addOpacityToColor($config, 'strokeColorHover', 'opacityHover');
        }
        $entity->setConfiguration($config);
    }


}
