<?php
namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\StaticView;
use Mapbender\Component\Element\TemplateView;
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
        return ['js' => ['@MapbenderCoreBundle/Resources/public/elements/MbCopyright.js']];
    }

    public function getClientConfiguration(Element $element): array
    {
        $config = parent::getClientConfiguration($element);
        $config['modal'] = true;
        return $config;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {

        return array(
            'autoOpen' => false,
            'content' => null,
            'dontShowAgain' => false,
            'dontShowAgainLabel' => 'mb.core.copyright.admin.dontShowAgainDefaultLabel',
            'popupWidth'    => 300,
            'popupHeight' => null,
            'element_icon' => self::getDefaultIcon(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbCopyright';
    }

    public function getView(Element $element)
    {
        $config = $element->getConfiguration();

        $view = new TemplateView('@MapbenderCore/Element/copyright.html.twig');
        $view->attributes['class'] = 'mb-element-copyright';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['content'] = $config['content'];
        $view->variables['dontShowAgain'] = $config['dontShowAgain'];
        $view->variables['dontShowAgainLabel'] = $config['dontShowAgainLabel'];

        // Do not cache if content contains any twig expressions or flow control ("{{" or "{%")
        if (!empty($config['content']) && false !== strpos($config['content'], '{')) {
            $view->cacheable = false;
        }

        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/copyright.html.twig';
    }

    public static function getDefaultIcon()
    {
        return 'iconCopyright';
    }
}
