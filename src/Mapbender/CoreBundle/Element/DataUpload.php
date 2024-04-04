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
        return 'mapbender.mbDataUpload';
    }

    /**
     * @inheritdoc
     */
    public function getRequiredAssets(Element $element)
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/element/dataupload.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/element/dataupload.scss',
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
            'helpText' => 'mb.core.dataupload.admin.helpLabel',
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
        $view->attributes['class'] = 'mb-element-dataupload';
        $view->attributes['data-title'] = $element->getTitle();
        return $view;
    }
}
