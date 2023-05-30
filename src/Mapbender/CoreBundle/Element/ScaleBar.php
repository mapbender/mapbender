<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\ConfigMigrationInterface;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * @author Paul Schmidt
 */
class ScaleBar extends AbstractElementService implements ConfigMigrationInterface, FloatableElement
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.scalebar.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.scalebar.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => 'Scale Bar',
            'maxWidth' => 200,
            'anchor' => 'right-bottom',
            'units' => "km",
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbScalebar';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleBarAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:scalebar.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.scalebar.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/scalebar.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:scalebar.html.twig');
        // @todo: fix template to include a text display area that doesn't require CSS positioning / sizing hacks
        $view->attributes['class'] = 'mb-element-scaleline smallText';
        $config = $element->getConfiguration() ?: array();
        $maxWidth = \intval(ArrayUtil::getDefault($config, 'maxWidth', null) ?: $this->getDefaultConfiguration()['maxWidth']);
        $view->attributes['style'] = "width: auto; min-width: {$maxWidth}px;";
        return $view;
    }

    public static function updateEntityConfig(Element $entity)
    {
        $config = $entity->getConfiguration();
        if (!empty($config['units'])) {
            // demote legacy multi-units array to scalar
            if (\is_array($config['units'])) {
                // use first value
                $vals = \array_values($config['units']);
                $config['units'] = $vals[0];
            }
        } else {
            // Drop falsy / empty array values. Defaults will be used automatically.
            unset($config['units']);
        }
        $entity->setConfiguration($config);
    }
}
