<?php
namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Element\Type\ScaleSelectorAdminType;
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
    public static function getClassTitle(): string
    {
        return "mb.core.scaleselector.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.scaleselector.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbScaleSelector.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/scaleselector.scss',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return ScaleSelectorAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'label' => false,
            "tooltip" => static::getClassTitle(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbScaleSelector';
    }

    public function getClientConfiguration(Element $element)
    {
        $config = parent::getClientConfiguration($element);
        $config['options'] = $this->getScales(ApplicationUtil::getMapElement($element->getApplication()));
        return $config;
    }

    public function getView(Element $element): TemplateView
    {
        $config = $element->getConfiguration() ?: [];
        $defaults = static::getDefaultConfiguration();
        $title = $element->getTitle() ?: static::getClassTitle();
        $view = new TemplateView('@MapbenderCore/Element/scaleselector.html.twig');
        $view->attributes['class'] = 'mb-element-scaleselector';
        $view->attributes['title'] = ArrayUtil::getDefault($config, 'tooltip', $title);
        $map = ApplicationUtil::getMapElement($element->getApplication());
        $view->variables = [
            'show_label' => ArrayUtil::getDefault($config, 'label', $defaults['label']),
            'scales' => $this->getScales($map),
            'title' => $title,
        ];
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderManager/Element/scaleselector.html.twig';
    }

    private function getScales(?Element $map): array
    {
        $scales = [];
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
