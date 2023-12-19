<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\FloatableElement;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\CoreBundle\Utils\ArrayUtil;

/**
 * @author Paul Schmidt
 */
class ScaleDisplay extends AbstractElementService implements FloatableElement
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.scaledisplay.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.scaledisplay.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'title' => self::getClassTitle(),
            'unitPrefix' => false,
            'scalePrefix' => 'mb.core.scaledisplay.label',
            'anchor' => 'right-bottom',
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbScaledisplay';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ScaleDisplayAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:scaledisplay.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.scaledisplay.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/scaledisplay.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:scaledisplay.html.twig');
        $view->attributes['class'] = 'mb-element-scaledisplay';
        $config = $element->getConfiguration() ?: array();
        $view->variables['scalePrefix'] = ArrayUtil::getDefault($config, 'scalePrefix', $this->getDefaultConfiguration()['scalePrefix']);
        return $view;
    }
}
