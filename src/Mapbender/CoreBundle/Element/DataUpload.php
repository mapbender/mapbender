<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Element\Type\DataUploadAdminType;
use Mapbender\Component\Element\AbstractElementService;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Component\Element\TemplateView;

class DataUpload extends AbstractElementService
{
    /**
     * @inheritdoc
     */
    public static function getClassTitle(): string
    {
        return 'mb.core.dataupload.class.title';
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription(): string
    {
        return 'mb.core.dataupload.class.description';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element): string
    {
        return 'MbDataUpload';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element): array
    {
        return [
            'js' => [
                '@MapbenderCoreBundle/Resources/public/elements/MbDataUpload.js',
            ],
            'css' => [
                '@MapbenderCoreBundle/Resources/public/sass/element/dataupload.scss',
            ],
            'trans' => [
                'mb.core.dataupload.*',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration(): array
    {
        return [
            'maxFileSize' => 10,
            'helpText' => 'mb.core.dataupload.admin.helpText',
            'element_icon' => self::getDefaultIcon(),
        ];
    }

    /**
     * @inheritdoc
     */
    public static function getType(): string
    {
        return DataUploadAdminType::class;
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate(): string
    {
        return '@MapbenderCore/ElementAdmin/dataupload.html.twig';
    }

    public function getClientConfiguration(Element $element)
    {
        return $element->getConfiguration();
    }

    public function getView(Element $element): TemplateView
    {
        $view = new TemplateView('@MapbenderCore/Element/dataupload.html.twig');
        $view->variables['title'] = $element->getTitle();
        $view->variables['helpText'] = $element->getConfiguration()['helpText'];
        $view->attributes['class'] = 'mb-element-dataupload me-3';
        $view->attributes['data-title'] = $element->getTitle();
        return $view;
    }

    public static function getDefaultIcon(): string
    {
        return 'iconDataUpload';
    }
}
