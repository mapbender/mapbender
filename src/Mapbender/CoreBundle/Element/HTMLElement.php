<?php

namespace Mapbender\CoreBundle\Element;

use Doctrine\DBAL\Connection;
use Mapbender\CoreBundle\Component\Element;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * HTMLElement.
 */
class HTMLElement extends Element
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.htmlelement.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.htmlelement.class.description";
    }

    /**
     * @inheritdoc
     */
    public static function getClassTags()
    {
        return array(
            "mb.core.htmlelement.tag.html");
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
            'content' =>  ''
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbHTMLElement';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render(
            'MapbenderCoreBundle:Element:htmlelement.html.twig',
            array(
                'id'            => $this->getId(),
                'entity'        => $this->entity,
                'application'   => $this->application,
                'configuration' => $this->getConfiguration()
            )
        );
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
    public static function getFormAssets()
    {
        return array(
            'js'  => array(
                'bundles/mapbendermanager/codemirror/lib/codemirror.js',
                'bundles/mapbendermanager/codemirror/mode/xml/xml.js',
                'bundles/mapbendermanager/codemirror/keymap/sublime.js',
                'bundles/mapbendermanager/codemirror/addon/selection/active-line.js',
                'bundles/mapbendercore/mapbender.admin.htmlelement.js',
            ),
            'css' => array(
                'bundles/mapbendermanager/codemirror/lib/codemirror.css',
                'bundles/mapbendermanager/codemirror/theme/neo.css',
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
                        $options[current($option)] = end($option);
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
                    foreach($dataStore->search() as $dataItem){
                        $options[$dataItem->getId()] = $dataItem->getAttribute($dataStoreInfo["text"]);
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
        return $assets;
    }

}
