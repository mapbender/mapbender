<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\BoundConfigMutator;
use Mapbender\CoreBundle\Entity;
use Mapbender\ManagerBundle\Component\Mapper;

/**
 * Map's overview element
 *
 * @author Paul Schmidt
 */
class BaseSourceSwitcher extends Element implements BoundConfigMutator
{

    /**
     * @inheritdoc
     */
    public static function getClassTitle()
    {
        return "mb.core.basesourceswitcher.class.title";
    }

    /**
     * @inheritdoc
     */
    public static function getClassDescription()
    {
        return "mb.core.basesourceswitcher.class.Description";
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => "BaseSourceSwitcher",
            'target' => null,
            'instancesets' => array(),
        );
    }

    /**
     * @inheritdoc
     */
    public function getWidgetName()
    {
        return 'mapbender.mbBaseSourceSwitcher';
    }

    /**
     * @inheritdoc
     */
    public static function getType()
    {
        return 'Mapbender\CoreBundle\Element\Type\BaseSourceSwitcherAdminType';
    }

    /**
     * @inheritdoc
     */
    public static function getFormTemplate()
    {
        return 'MapbenderCoreBundle:ElementAdmin:basesourceswitcher.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function getAssets()
    {
        return array(
            'js' => array(
                '@MapbenderCoreBundle/Resources/public/mapbender.element.basesourceswitcher.js',
            ),
            'css' => array(
                '@MapbenderCoreBundle/Resources/public/sass/element/basesourceswitcher.scss',
            ),
            'trans' => array(
                'mb.core.basesourceswitcher.error.*',
            ),
        );
    }

    protected function mergeGroups(Entity\Element $element)
    {
        $rawConf = $element->getConfiguration();
        $itemsOut = array();
        if (empty($rawConf['instancesets']) || !is_array($rawConf['instancesets'])) {
            // @todo: throw config error if wrong type, complain about empty array
            $itemConfigs = array();
        } else {
            $itemConfigs = $rawConf['instancesets'];
        }
        foreach ($itemConfigs as $itemIn) {
            $itemOut = array(
                'type'    => 'item',
                'title'   => $itemIn['title'],
                'sources' => $itemIn['instances']
            );
            $isGroup = !empty($itemIn['group']);
            if ($isGroup) {
                $groupName = $itemIn['group'];
                if (empty($itemsOut[$groupName])) {
                    $itemsOut[$groupName] = array(
                        'type' => 'group',
                        'title' => $groupName,
                        'items' => array(),
                    );
                }
                $itemsOut[$groupName]['items'][] = $itemOut;
            } else {
                $itemsOut[$itemIn['title']] = $itemOut;
            }
        }
        foreach ($itemsOut as &$firstGroup) {
            $firstGroup['active'] = true;
            if ($firstGroup['type'] == 'group' && $firstGroup['items']) {
                $firstGroup['items'][0]['active'] = true;
            }
            break;
        }
        return $itemsOut;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:basesourceswitcher.html.twig';
    }

    public function getFrontendTemplateVars()
    {
        $rawConf = $this->entity->getConfiguration();
        return array(
            'id' => $this->entity->getId(),
            'title' => $this->entity->getTitle(),
            'configuration' => $rawConf + array(
                'groups' => $this->mergeGroups($this->entity),
            ),
        );
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        $template = $this->getFrontendTemplatePath();
        $vars = $this->getFrontendTemplateVars();
        return $this->container->get('templating')->render($template, $vars);
    }

    /**
     * @inheritdoc
     */
    public function denormalizeConfiguration(array $configuration, Mapper $mapper)
    {
        foreach ($configuration['instancesets'] as $key => &$instanceset) {
            foreach ($instanceset['instances'] as &$instance) {
                if ($instance) {
                    $instance =
                        $mapper->getIdentFromMapper('Mapbender\CoreBundle\Entity\SourceInstance', $instance, true);
                }
            }
        }
        return $configuration;
    }

    /**
     * Returns an array with 'active' and 'inactive' lists of source ids, in the initial state after loading.
     *
     * return int[][]
     */
    public function getDefaultSourceVisibility()
    {
        $rv = array(
            'active' => array(),
            'inactive' => array(),
        );
        $config = $this->getConfiguration();

        foreach ($config['groups'] as $menuEntry) {
            switch ($menuEntry['type']) {
                case 'item':
                    // wrap single item as array
                    $menuItems = array($menuEntry);
                    break;
                case 'group':
                    // process submenu items
                    $menuItems = $menuEntry['items'];
                    break;
                default:
                    throw new \RuntimeException("Unexpected menu item type " . var_export($menuEntry['type'], true));
            }
            foreach ($menuItems as $menuItem) {
                $destKey = (!empty($menuItem['active'])) ? 'active' : 'inactive';
                $rv[$destKey] = array_merge($rv[$destKey], $menuItem['sources']);
            }
        }
        return $rv;
    }

    /**
     * @inheritdoc
     */
    public function updateAppConfig($config)
    {
        $controlledSourceIds = $this->getDefaultSourceVisibility();

        /**
         * @todo: evaluate "target" (e.g. main map vs overview map) and only process
         *        layers bound to that target
         */
        foreach ($config['layersets'] as &$layersetConfig) {
            foreach ($layersetConfig['instances'] as &$instanceConfig) {
                $layerId = $instanceConfig['id'];
                if (in_array($layerId, $controlledSourceIds['active'])) {
                    $setActive = true;
                } elseif (in_array($layerId, $controlledSourceIds['inactive'])) {
                    $setActive = false;
                } else {
                    // layer is not controllable through BSS, leave its config alone
                    continue;
                }
                if (!empty($instanceConfig['configuration']['children'])) {
                    foreach ($instanceConfig['configuration']['children'] as &$chDef) {
                        $chDef['options']['treeOptions']['selected'] = $setActive;
                    }
                }
            }
        }
        return $config;
    }
}
