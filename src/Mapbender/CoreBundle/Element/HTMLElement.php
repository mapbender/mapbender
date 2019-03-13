<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Psr\Log\LoggerInterface;

/**
 * HTMLElement.
 */
class HTMLElement extends Element
{
    public static function getClassTitle()
    {
        return 'mb.core.htmlelement.class.title';
    }

    public static function getClassDescription()
    {
        return 'mb.core.htmlelement.class.description';
    }

    public function getWidgetName()
    {
        // no script constructor
        return false;
    }

    public static function getClassTags()
    {
        return array(
            'mb.core.htmlelement.tag.html',
        );
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'mapbender.form_type.element.htmlelement';
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'classes' => 'html-element-inline',
            'content' => ''
        );
    }

    /**
     * Render markup.
     * Because the entire template is user-configurable, we add some error handling here.
     *
     * @return string
     */
    public function render()
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get('logger');

        try {
            return parent::render();
        } catch (\Twig_Error $e) {
            $message = "Invalid content in " . get_class($this) . " caused " . get_class($e);
            $logger->warning($message . ", suppressing content", $this->getConfiguration());
            return "<div id=\"{$this->getEntity()->getId()}\"><!-- $message --></div>";
        }
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:htmlelement.html.twig';
    }

    /**
     * @inheritdoc
     */
    public static function listAssets()
    {
        return array(
            'js'  => array('/bundles/mapbendercore/mapbender.element.htmlelement.js'),
            'css' => array('/bundles/mapbendercore/sass/element/htmlelement.scss')
        );
    }
}
