<?php
namespace Mapbender\CoreBundle\Element;


use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;
use Mapbender\Utils\ApplicationUtil;

/**
 * A ScaleSelector
 *
 * Displays and changes a map scale.
 *
 * @author Paul Schmidt
 */
class ScaleSelector extends AbstractElementService
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.scaleselector.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.scaleselector.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/elements/MbScaleSelector.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.scaleselector.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleSelectorAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'label' => false,
            "tooltip" => static::getClassTitle(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbScaleSelector';
    }

    public function getClientConfiguration(Element $element)
    {
        $config = parent::getClientConfiguration($element);
        $config['options'] = $this->getScales(ApplicationUtil::getMapElement($element->getApplication()));
        return $config;
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration() ?: array();
        $defaults = $this->getDefaultConfiguration();
        $title = $element->getTitle() ?: $this->getClassTitle();
        $view = new TemplateView('@MapbenderCore/Element/scaleselector.html.twig');
        $view->attributes['class'] = 'mb-element-scaleselector';
        $view->attributes['title'] = ArrayUtil::getDefault($config, 'tooltip', $title);
        $map = ApplicationUtil::getMapElement($element->getApplication());
        $view->variables = array(
            'show_label' => ArrayUtil::getDefault($config, 'label', $defaults['label']),
            'scales' => $this->getScales($map),
            'title' => $title,
        );
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderManager/Element/scaleselector.html.twig';
    }

    private function getScales(?Element $map): array
    {
        $scales = array();
        if ($map) {
            $mapConfig = $map->getConfiguration();
            if (!empty($mapConfig['scales'])) {
                $scales = $mapConfig['scales'];
                asort($scales, SORT_NUMERIC | SORT_REGULAR);
            }
        }
        return array_values($scales);
    }

}
