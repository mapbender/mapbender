<?php


namespace Mapbender\Component\Element;


use Mapbender\CoreBundle\Entity\Element;

abstract class ButtonLike extends AbstractElementService
{
    public static function getFormTemplate()
    {
        return 'MapbenderManagerBundle:Element:button.html.twig';
    }

    public static function getDefaultConfiguration()
    {
        return array(
            /** @see \Mapbender\CoreBundle\Element\Type\BaseButtonAdminType::buildForm */
            'label' => true,
            'tooltip' => null,
            'icon' => null,
        );
    }

    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/button.scss',
            ),
            'trans' => array(
            ),
        );
    }

    /**
     * Initializes view variables + attributes for local configuration values.
     * Not called internally. Provided for child classes.
     *
     * @param TemplateView $view
     * @param Element $element
     */
    protected function initializeView(TemplateView $view, Element $element)
    {
        $view->attributes['class'] = 'mb-button';

        $config = $element->getConfiguration();

        $label = $element->getTitle() ?: $this->getClassTitle();
        $view->variables['icon'] = $config['icon'];
        $view->variables['label'] = $label;
        $view->attributes['title'] = $config['tooltip'] ?: $label;
        $view->variables['show_label'] = $config['label'];
    }
}
