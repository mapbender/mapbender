<?php

namespace Mapbender\CoreBundle\Element;

use Doctrine\DBAL\Connection;
use Mapbender\CoreBundle\Component\Element;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

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

    /**
     * Is associative array given?
     *
     * @param $arr
     * @return bool
     * @deprecated will be removed in 3.0.8.0
     */
    protected static function isAssoc(&$arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Prepare elements recursive.
     *
     * @param $items
     * @return array
     * @deprecated will be removed in 3.0.8.0
     * @internal param $configuration
     */
    protected function prepareItems($items)
    {
        if (!is_array($items)) {
            return $items;
        } elseif (self::isAssoc($items)) {
            $items = $this->prepareItem($items);
        } else {
            foreach ($items as $key => $item) {
                $items[$key] = $this->prepareItem($item);
            }
        }
        return $items;
    }

    /**
     * Prepare element by type
     *
     * @param $item
     * @return mixed
     * @internal
     * @deprecated will be removed in 3.0.8.0
     */
    protected function prepareItem($item)
    {
        if (!isset($item["type"])) {
            return $item;
        }

        if (isset($item["children"])) {
            $item["children"] = $this->prepareItems($item["children"]);
        }

        switch ($item['type']) {
            case 'select':
                if (isset($item['sql'])) {
                    $connectionName = isset($item['connection']) ? $item['connection'] : 'default';
                    $sql            = $item['sql'];
                    $options        = isset($item["options"]) ? $item["options"] : array();

                    unset($item['sql']);
                    unset($item['connection']);

                    /** @var Connection $dbal */
                    $dbal = $this->container->get("doctrine.dbal.{$connectionName}_connection");
                    foreach ($dbal->fetchAll($sql) as $option) {
                        $options[current($option)] = end($option);
                    }
                    $item["options"] = $options;
                }
                break;
        }
        return $item;
    }

    /**
     * @inheritdoc
     */
    public function httpAction($action)
    {
        switch ($action) {
            case 'configuration':
                return new JsonResponse($this->getConfiguration());
        }
    }

    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = parent::getConfiguration();
        if (isset($configuration['children'])) {
            $configuration['children'] = $this->prepareItems($configuration['children']);
        }
        return $configuration;
    }
}
