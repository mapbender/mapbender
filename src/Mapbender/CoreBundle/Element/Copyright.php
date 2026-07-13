<?php
namespace Mapbender\CoreBundle\Element;

use Twig\Environment;
use Mapbender\CoreBundle\Element\Type\CopyrightAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

/**
 * A Copyright
 *
 * Displays a copyright label and terms of use.
 *
 * @author Paul Schmidt
 */
class Copyright extends AbstractElementService
{
    public function __construct(protected Environment $templateEngine)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.copyright.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.copyright.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return CopyrightAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
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
    public static function getDefaultConfiguration(): array
    {

        return [
            'autoOpen' => false,
            'content' => null,
            'dontShowAgain' => false,
            'dontShowAgainLabel' => 'mb.core.copyright.admin.dontShowAgainDefaultLabel',
            'popupWidth'    => 300,
            'popupHeight' => null,
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbCopyright';
    }

    public function getView(Element $element): TemplateView
    {
        $config = $element->getConfiguration();

        $view = new TemplateView('@MapbenderCore/Element/copyright.html.twig');
        $view->attributes['class'] = 'mb-element-copyright';
        $view->attributes['data-title'] = $element->getTitle();
        $view->variables['content'] = $config['content'];
        $view->variables['dontShowAgain'] = $config['dontShowAgain'];
        $view->variables['dontShowAgainLabel'] = $config['dontShowAgainLabel'];

        // Do not cache if content contains any twig expressions or flow control ("{{" or "{%")
        if (!empty($config['content']) && str_contains($config['content'], '{')) {
            $view->cacheable = false;
        }

        return $view;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/copyright.html.twig';
    }

    public static function getDefaultIcon(): string
    {
        return 'iconCopyright';
    }
}
