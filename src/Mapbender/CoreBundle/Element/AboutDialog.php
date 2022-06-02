<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Twig;

class AboutDialog extends ButtonLike implements ElementHttpHandlerInterface
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
        return $view;
    }

    public function getHttpHandler(Element $element)
    {
        return $this; // :)
    }

    public function handleRequest(Element $element, Request $request)
    {
        $action = $request->attributes->get('action');
        switch ($action) {
            case 'content':
                $content = $this->templateEngine->render('MapbenderCoreBundle:Element:about_dialog_content.html.twig');
                return new Response($content);
            default:
                throw new BadRequestHttpException("Unsupported action {$action}");
        }
    }
}
