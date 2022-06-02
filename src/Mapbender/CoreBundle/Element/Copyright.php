<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\ElementHttpHandlerInterface;
use Mapbender\Component\Element\StaticView;
use Mapbender\CoreBundle\Entity\Element;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Twig;

/**
 * A Copyright
 * 
 * Displays a copyright label and terms of use.
 * 
 * @author Paul Schmidt
 */
class Copyright extends AbstractElementService implements ElementHttpHandlerInterface
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
        return "mb.core.copyright.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.copyright.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\CopyrightAdminType';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.copyright.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/copyright.scss',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {

        return array(
            'autoOpen' => false,
            'content' => null,
            'popupWidth'    => 300,
            'popupHeight' => null,
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'mapbender.mbCopyright';
    }

    public function getView(Element $element)
    {
        $view = new StaticView('');
        $view->attributes['class'] = 'mb-element-copyright';
        $view->attributes['data-title'] = $element->getTitle();
        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:copyright.html.twig';
    }

    public function getHttpHandler(Element $element)
    {
        return $this;
    }

    public function handleRequest(Element $element, Request $request)
    {
        switch ($request->attributes->get('action')) {
            case 'content':
                $content = $this->templateEngine->render('MapbenderCoreBundle:Element:copyright-content.html.twig', array(
                    'configuration' => $element->getConfiguration(),
                ));
                return new Response($content);
            default:
                throw new NotFoundHttpException();
        }
    }
}
