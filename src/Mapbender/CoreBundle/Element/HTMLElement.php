<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Psr\Log\LoggerInterface;
use Twig\Error\Error;

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

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\HTMLElementAdminType';
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

    public function getFrontendTemplateVars()
    {
        $config = $this->entity->getConfiguration();
        if (!empty($config['classes'])) {
            $cssClassNames = array_map('trim', explode(' ', $config['classes']));
        } else {
            $cssClassNames = array();
        }
        if (in_array('html-element-inline', $cssClassNames)) {
            $tagName = 'span';
        } else {
            $tagName = 'div';
        }
        return array(
            'configuration' => $config,
            'tagName' => $tagName,
            'application' => $this->entity->getApplication(),
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
        } catch (Error $e) {
            $message = "Invalid content in " . get_class($this) . " caused " . get_class($e);
            $logger->warning($message . ", suppressing content", $this->getConfiguration());
            return "<div id=\"{$this->getEntity()->getId()}\"><!-- $message --></div>";
        }
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:htmlelement.html.twig';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:htmlelement.html.twig';
    }
}
