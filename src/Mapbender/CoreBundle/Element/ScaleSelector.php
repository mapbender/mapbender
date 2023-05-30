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
                '@MapbenderCoreBundle/Resources/public/mapbender.element.scaleselector.js',
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
        return 'mapbender.mbScaleSelector';
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration() ?: array();
        $defaults = $this->getDefaultConfiguration();
        $title = $element->getTitle() ?: $this->getClassTitle();
        $view = new TemplateView('MapbenderCoreBundle:Element:scaleselector.html.twig');
        $view->attributes['class'] = 'mb-element-scaleselector';
        $view->attributes['title'] = ArrayUtil::getDefault($config, 'tooltip', $title);
        $map = ApplicationUtil::getMapElement($element->getApplication());
        $scales = array();
        if ($map) {
            $mapConfig = $map->getConfiguration();
            if (!empty($mapConfig['scales'])) {
                $scales = $mapConfig['scales'];
                asort($scales, SORT_NUMERIC | SORT_REGULAR);
            }
        }
        $view->variables = array(
            'show_label' => ArrayUtil::getDefault($config, 'label', $defaults['label']),
            'scales' => $scales,
            'title' => $title,
        );
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:scaleselector.html.twig';
    }

}
