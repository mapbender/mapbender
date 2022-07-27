<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Twig;

class AboutDialog extends ButtonLike
{
    /** @var Twig\Environment */
    protected $templateEngine;

    public function __construct(Twig\Environment $templateEngine)
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.aboutdialog.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.aboutdialog.class.description";
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        $required = parent::getRequiredAssets($element) + array(
            'js' => array(),
        );
        $required['js'] = array_merge($required['js'], array(
            '@MapbenderCoreBundle/Resources/public/mapbender.element.aboutDialog.js',
        ));
        return $required;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        $defaults = array_replace(parent::getDefaultConfiguration(), array(
            "tooltip" => "About",
        ));
        unset($defaults['icon']);
        return $defaults;
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\AboutDialogAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbAboutDialog';
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('MapbenderCoreBundle:Element:about_dialog.html.twig');
        $this->initializeView($view, $element);
        $view->attributes['class'] = 'mb-button mb-aboutButton';
        $templateName = $this->getContentTemplateName($element);
        $template = $this->templateEngine->getLoader()->getSourceContext($templateName);
        $templateContent = $template->getCode();
        // Do not cache if content contains any twig expressions or flow control ("{{" or "{%")
        if (false !== strpos($templateContent, '{')) {
            $view->cacheable = false;
        }
        $view->variables['content'] = $this->templateEngine->render($template->getName());

        return $view;
    }

    protected function getContentTemplateName(Element $element)
    {
        return 'MapbenderCoreBundle:Element:about_dialog_content.html.twig';
    }
}
