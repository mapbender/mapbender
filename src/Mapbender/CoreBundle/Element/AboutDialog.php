<?php
namespace Mapbender\CoreBundle\Element;

use Twig\Environment;
use Mapbender\CoreBundle\Element\Type\BaseButtonAdminType;
use Mapbender\Component\Element\ButtonLike;
use Mapbender\Component\Element\TemplateView;
use Mapbender\CoreBundle\Entity\Element;

class AboutDialog extends ButtonLike
{
    public function __construct(protected Environment $templateEngine)
    {
    }

    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return "mb.core.aboutdialog.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return "mb.core.aboutdialog.class.description";
    }

    /**
     * @inheritdoc
     * @return mixed[]
     */
    public function getRequiredAssets(Element $element): array
    {
        $required = parent::getRequiredAssets($element) + [
            'js' => [],
        ];
        $required['js'] = array_merge($required['js'], [
            '@MapbenderCoreBundle/Resources/public/elements/MbAboutDialog.js',
        ]);
        return $required;
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return array_replace(parent::getDefaultConfiguration(), [
            "tooltip" => "mb.core.aboutdialog.admin.tooltip",
            "icon" => self::getDefaultIcon(),
            "element_icon" => self::getDefaultIcon(),
        ]);
    }

    public function getClientConfiguration(Element $element): array
    {
        $configuration = parent::getClientConfiguration($element);
        if (empty($configuration['icon'])) {
            $configuration['icon'] = self::getDefaultIcon();
        }
        return $configuration;
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return BaseButtonAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbAboutDialog';
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/about_dialog.html.twig');
        $this->initializeView($view, $element);
        $view->attributes['class'] = 'mb-button mb-aboutButton';
        $view->attributes['tabindex'] = '0';
        $templateName = $this->getContentTemplateName($element);
        $template = $this->templateEngine->getLoader()->getSourceContext($templateName);
        $templateContent = $template->getCode();
        // Do not cache if content contains any twig expressions or flow control ("{{" or "{%")
        if (str_contains($templateContent, '{')) {
            $view->cacheable = false;
        }
        $view->variables['content'] = $this->templateEngine->render($template->getName());

        return $view;
    }

    protected function getContentTemplateName(Element $element): string
    {
        return '@MapbenderCore/Element/about_dialog_content.html.twig';
    }

    public static function getDefaultIcon(): string
    {
        return 'iconAbout';
    }
}
