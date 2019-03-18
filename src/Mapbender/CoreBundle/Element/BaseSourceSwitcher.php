<?php

namespace Mapbender\CoreBundle\Element;

use Mapbender\CoreBundle\Component\Element;
use Mapbender\CoreBundle\Component\ElementBase\BoundConfigMutator;
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
    public static function getClassTags()
    {
        return array(
            "mb.core.basesourceswitcher.tag.base",
            "mb.core.basesourceswitcher.tag.source",
            "mb.core.basesourceswitcher.tag.switcher"
        );
    }

    /**
     * @inheritdoc
     */
    public static function getDefaultConfiguration()
    {
        return array(
            'tooltip' => "BaseSourceSwitcher",
            'target' => null,
            'instancesets' => array()
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
                'MapbenderCoreBundle:Element:basesourceswitcher.json.twig',
            ),
        );
    }
    
    /**
     * @inheritdoc
     */
    public function getConfiguration()
    {
        $configuration = $confHelp = parent::getConfiguration();
        if (isset($configuration['instancesets'])) {
            unset($configuration['instancesets']);
        }
        $configuration['groups'] = array();
        if (!isset($confHelp['instancesets']) || !is_array($confHelp['instancesets'])) {
            $confHelp['instancesets'] = array();
        }
        foreach ($confHelp['instancesets'] as $instanceset) {
            if (isset($instanceset['group']) && $instanceset['group'] !== '') {
                if (!isset($configuration['groups'][$instanceset['group']])) {
                    $configuration['groups'][$instanceset['group']] = array(
                        'type'  => 'group',
                        'items' => array(),
                    );
                }
                $configuration['groups'][$instanceset['group']]['items'][] = array(
                    'title'   => $instanceset['title'],
                    'sources' => $instanceset['instances']
                );
            } else {
                $configuration['groups'][$instanceset['title']] = array(
                    'type'    => 'item',
                    'title'   => $instanceset['title'],
                    'sources' => $instanceset['instances']
                );
            }
        }
        foreach ($configuration['groups'] as &$firstGroup) {
            $firstGroup['active'] = true;
            if ($firstGroup['type'] == 'group' && $firstGroup['items']) {
                $firstGroup['items'][0]['active'] = true;
            }
            break;
        }
        return $configuration;
    }

    public function getFrontendTemplatePath($suffix = '.html.twig')
    {
        return 'MapbenderCoreBundle:Element:basesourceswitcher.html.twig';
    }

    /**
     * @inheritdoc
     */
    public function render()
    {
        return $this->container->get('templating')->render($this->getFrontendTemplatePath(), array(
            'id' => $this->getId(),
            "title" => $this->getTitle(),
            'configuration' => $this->getConfiguration()
        ));
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
        foreach ($config['layersets'] as &$layerList) {
            foreach ($layerList as &$layerMap) {
                foreach ($layerMap as $layerId => &$layerDef) {
                    if (in_array($layerId, $controlledSourceIds['active'])) {
                        $setActive = true;
                    } elseif (in_array($layerId, $controlledSourceIds['inactive'])) {
                        $setActive = false;
                    } else {
                        // layer is not controllable through BSS, leave its config alone
                        continue;
                    }
                    $layerDef['configuration']['options']['visible'] = $setActive;
                    if (!empty($layerDef['configuration']['children'])) {
                        foreach ($layerDef['configuration']['children'] as &$chDef) {
                            $chDef['options']['treeOptions']['selected'] = $setActive;
                        }
                    }
                }
            }
        }
        return $config;
    }
}
