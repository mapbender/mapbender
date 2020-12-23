<?php


namespace Mapbender\CoreBundle\Element;


use Mapbender\CoreBundle\Component\ElementBase\MinimalInterface;

class ControlButton extends BaseButton
{
    // Disable being targetted by any other Button
    public static $ext_api = false;

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

    public function getWidgetName()
    {
        return 'mapbender.mbControlButton';
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

    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.button.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/button.scss',
            ),
        );
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:control_button.html.twig';
    }

    public function getFrontendTemplateVars()
    {
        $title = $this->entity->getTitle();
        if (!$title) {
            $target = $this->entity->getTargetElement('target');
            if ($target) {
                $title = $target->getTitle();
                if ($target && $target->getClass()) {
                    /** @var MinimalInterface|string $targetClass */
                    $targetClass = $target->getClass();
                    $title = $targetClass::getClassTitle();
                }
            }
        }
        if (!$title) {
            $title = $this->getClassTitle();
        }
        return array(
            'configuration' => $this->entity->getConfiguration(),
            'title' => $title,
        );
    }
}
