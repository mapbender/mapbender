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
    protected static $title                = "mb.core.htmlelement.class.title";
    protected static $description          = "mb.core.htmlelement.class.description";
    protected static $tags                 = array(
        "mb.core.htmlelement.tag.html"
    );
    protected static $defaultConfiguration = array(
        'classes' => 'html-element-inline',
        'content' => ''
    );

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
     * @inheritdoc
     */
    public static function getFormAssets()
    {
        return array(
            'js'  => array(
                'components/codemirror/lib/codemirror.js',
                'components/codemirror/mode/xml/xml.js',
                'components/codemirror/keymap/sublime.js',
                'components/codemirror/addon/selection/active-line.js',
                'bundles/mapbendercore/mapbender.admin.htmlelement.js',
            ),
            'css' => array(
                'components/codemirror/lib/codemirror.css',
                'components/codemirror/theme/neo.css',
            )
        );
    }

    /**
     * Is associative array given?
     *
     * @param $arr
     * @return bool
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
     */
    public function prepareItems($items)
    {
        if (!is_array($items)) {
            return $items;
        } elseif (self::isAssoc($items)) {
            $items = $this->prepareItem($items);
        } else {
            foreach ($items as $key => $item) {
                $items[ $key ] = $this->prepareItem($item);
            }
        }
        return $items;
    }

    /**
     * Prepare element by type
     *
     * @param $item
     * @return mixed
     * @internal param $type
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
                        $options[ current($option) ] = end($option);
                    }
                    $item["options"] = $options;
                }

                if (isset($item['service'])) {
                    $serviceInfo = $item['service'];
                    $serviceName = isset($serviceInfo['serviceName']) ? $serviceInfo['serviceName'] : 'default';
                    $method      = isset($serviceInfo['method']) ? $serviceInfo['method'] : 'get';
                    $args        = isset($serviceInfo['args']) ? $item['args'] : '';
                    $service     = $this->container->get($serviceName);
                    $options     = $service->$method($args);

                    $item['options'] = $options;
                }

                if (isset($item['dataStore'])) {
                    $dataStoreInfo = $item['dataStore'];
                    $dataStore     = $this->container->get('data.source')->get($dataStoreInfo["id"]);
                    $options       = array();
                    foreach ($dataStore->search() as $dataItem) {
                        $options[ $dataItem->getId() ] = $dataItem->getAttribute($dataStoreInfo["text"]);
                    }
                    $item['options'] = $options;
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

    /**
     * Get asset list and add JS file if 'jsSrc' keyword configured.
     *
     * @inheritdoc
     */
    public function getAssets()
    {
        $configuration = $this->getConfiguration();
        $assets        = $this::listAssets();
        if (isset($configuration['jsSrc'])) {
            if (is_array($configuration['jsSrc'])) {
                $assets['js'] = array_merge($assets['js'], $configuration['jsSrc']);
            } else {
                $assets['js'][] = $configuration['jsSrc'];
            }
        }
        if (isset($configuration['css'])) {
            if (is_array($configuration['css'])) {
                $assets['css'] = array_merge($assets['css'], $configuration['css']);
            } else {
                $assets['css'][] = $configuration['css'];
            }
        }
        return $assets;
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
}
