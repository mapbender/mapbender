<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\StaticView;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Utils\HtmlUtil;
use Twig;

/**
 * A Copyright
 * 
 * Displays a copyright label and terms of use.
 * 
 * @author Paul Schmidt
 */
class Copyright extends AbstractElementService
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
        $config = $element->getConfiguration();
        // Do not cache if content contains any twig expressions or flow control ("{{" or "{%")
        $content = $this->templateEngine->createTemplate($config['content'] ?: '')->render(array(
            'configuration' => $config,
        ));
        $wrapped = HtmlUtil::renderTag('div', $content, array(
            'class' => 'hidden -js-popup-content',
        ));
        $view = new StaticView($wrapped);
        if (!empty($config['content']) && false !== strpos($config['content'], '{')) {
            $view->cacheable = false;
        }
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
}
