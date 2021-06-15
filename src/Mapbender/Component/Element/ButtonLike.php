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

        $view->variables['label'] = $element->getTitle() ?: $this->getClassTitle();
        if (!empty($config['icon'])) {
            $view->variables['icon'] = $config['icon'];
        } else {
            $view->variables['icon'] = '';
        }
        $view->variables['show_label'] = $config['label'];
        $view->attributes['title'] = $config['tooltip'] ?: $view->variables['label'];
    }
}
