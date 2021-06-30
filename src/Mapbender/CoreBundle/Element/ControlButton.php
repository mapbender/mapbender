<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\Component\ClassUtil;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;
use Mapbender\CoreBundle\Entity\Element;

class ControlButton extends ButtonLike
{
    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.button.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.controlbutton.class.description";
    }

    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbControlButton';
    }

    public function getRequiredAssets(Element $element)
    {
        $requirements = parent::getRequiredAssets($element) + array(
            'js' => array(),
        );
        $requirements['js'][] = '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js';
        return $requirements;
    }

    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\ControlButtonAdminType';
    }

    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:control_button.html.twig';
    }

    public static function getDefaultConfiguration()
    {
        return array_replace(parent::getDefaultConfiguration(), array(
            'group' => null,
            'target' => null,
        ));
    }

    public function getView(Element $element)
    {
        $target = $element->getTargetElement('target');
        if (!$target || !$target->getClass() || !ClassUtil::exists($target->getClass())) {
            return false;
        }

        $view = new TemplateView('MapbenderCoreBundle:Element:control_button.html.twig');
        $this->initializeView($view, $element);

        $config = $element->getConfiguration();
        $view->attributes['data-group'] = $config['group'];

        // Undo / replace parent label and tooltip fallbacks with target title
        $label = $element->getTitle() ?: $target->getTitle();
        if (!$label) {
            /** @var MinimalInterface|string $targetClass */
            $targetClass = $target->getClass();
            $label = $targetClass::getClassTitle();
        }
        $view->variables['label'] = $label;
        $view->attributes['title'] = $config['tooltip'] ?: $label;
        return $view;
    }
}
