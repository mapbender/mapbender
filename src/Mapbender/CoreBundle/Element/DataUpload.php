<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\Component\Element\AbstractElementService;
use Mapbender\CoreBundle\Entity\Element;
use Mapbender\Component\Element\TemplateView;

class DataUpload extends AbstractElementService
{
    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return 'mb.core.dataupload.class.title';
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return 'mb.core.dataupload.class.description';
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName(Element $element)
    {
        return 'MbDataUpload';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/elements/MbDataUpload.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/dataupload.scss',
            ),
            'trans' => array(
                'mb.core.dataupload.*',
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'maxFileSize' => 10,
            'helpText' => 'mb.core.dataupload.admin.helpText',
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\DataUploadAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return '@MapbenderCore/ElementAdmin/dataupload.html.twig';
    }

    public function getClientConfiguration(Element $element)
    {
        return $element->getConfiguration();
    }

    public function getView(Element $element)
    {
        $view = new TemplateView('@MapbenderCore/Element/dataupload.html.twig');
        $view->variables['title'] = $element->getTitle();
        $view->variables['helpText'] = $element->getConfiguration()['helpText'];
        $view->attributes['class'] = 'mb-element-dataupload me-3';
        $view->attributes['data-title'] = $element->getTitle();
        return $view;
    }
}
